<?php

// namespace my_server;
/**
 * SYS_VER : System version
 */
define('SYS_VER', '0.0.1');

/**
 * SYS_START_AT : System start at
 */
define('SYS_START_AT', date('Y-m-d H:i:s'));

/*
 * Enable autoload for the classes installed by composer
 */
require dirname(APP_PATH).'/vendor/autoload.php';

/*
 * Enable core components
 */
require SYS_PATH.'/core/utility.php';
require SYS_PATH.'/core/container.php';


class AppServer {
    // Global server instance
    private $_server;

    // Global unique object of server
    private static $_self = null;

    // Server config
    public $config = null;

    /**
     * $_apps : pool of application instance
     * @var array
     */
    private $_apps = array();

    /**
     * $_sys_plugins : system plugins
     * @var array
     */
    private $_sys_plugins;

    /**
     * __construct : constractor
     * @param  array  $config config object
     */
    private function __construct( $config=array() ) {
        // global configuration && error/exception handlers
        switch (SYS_ENV) {
            case 'production':
                ini_set('display_errors', false);
                error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
                break;
            case 'testing':
                ini_set('display_errors', true);
                error_reporting(E_ALL);
                break;
            case 'development':
            default:
                ini_set('display_errors', true);
                error_reporting(E_ALL);
                break;
        }
        register_shutdown_function( 'shutdown_handler' );
        set_error_handler( 'error_handler' );
        set_exception_handler( 'exception_handler' );

        // if( !$this->load_class('container', 'core', false) ){
        //     log_message('error', 'Failed to load class : container');
        // }

        // Load config
        if( !$this->load_config($config) ){
            // Failed to load config
        }

        $this->_sys_plugins = array();

        // default components
        $arrCls = array('plugin', 'mvc');
        $blResult = true;

        // load classes
        foreach ($arrCls as $idx => $cls_name) {
            if( !$this->load_class($cls_name, 'core', false) ){
                $blResult = false;
                log_message('error', 'Failed to load class : '.$cls_name);
            }
        }
    }

    /**
     * load_class : Load class by name
     * @param  string $cls_name     class name
     * @param  string $folder       sub folder contain the specified class definition
     * @param  string $check_class  check class or not
     * @return bool           TRUE for class loaded, otherwise return false
     */
    public function load_class($cls_name, $folder='', $check_class=false){
        if( empty($folder) ){
            $folder = 'core';
        }
        if( $check_class && class_exists(basename($cls_name), false) ){
            return true;
        }
        if( file_exists(APP_PATH.'/'.$folder.'/'.$cls_name.'.php') ){
            require APP_PATH.'/'.$folder.'/'.$cls_name.'.php';
        }elseif( file_exists(SYS_PATH.'/'.$folder.'/'.$cls_name.'.php') ){
            require SYS_PATH.'/'.$folder.'/'.$cls_name.'.php';
        }
        return $check_class ? class_exists(basename($cls_name), false) : true;
    }

    /**
     * get_instance : Get unique object of server
     * @param  array  $config config object
     * @return object   object of server
     */
    public static function get_instance( $config=array() ){
        if( empty(self::$_self) ){
            self::$_self = new AppServer($config);
        }
        return self::$_self;
    }

    /**
     * get_app : get app instance for current worker process
     * @return object  app instance
     */
    public function get_app(){
        if( isset($this->_apps[$this->_server->worker_id]) ){
            return $this->_apps[$this->_server->worker_id];
        }else{
            return new super_app();
        }
    }

    /**
     * load_config : Load config
     * @param  array  $config config object
     * @return object   Config object
     */
    public function load_config( $config=array() ) {
        if( !empty($this->config) ){
            return true;
        }

        $strCfg = APP_PATH.'/config/config.'.SYS_ENV.'.json';
        if( !file_exists($strCfg) ){
            $strCfg = APP_PATH.'/config/config.json';
        }
        if( !file_exists($strCfg) ){
            return false;
        }
        $strContent = file_get_contents($strCfg);
        $objCfg = json_decode($strContent,true);
        if( !empty($objCfg) ){
            $this->config = new array_container();
            $objCfg = array_merge($objCfg, $config);
            foreach ($objCfg as $key => $value) {
                $this->config->$key = $value;
            }
        }
        return true;
    }

    /**
     * start : start the server
     * @return bool     Operation result
     */
    public function start() {
        $serverPort = $this->config->get('server_port', 2048);
        $serverType = $this->config->get('server_type', 'http');

        // create server object
        if( $serverType==='http' ){
            $this->_server = new swoole_http_server(
                '0.0.0.0',
                $serverPort
            );
            $this->_server->on('Request',      array($this, 'on_request'));
            $this->_server->on('Message',      array($this, 'on_message'));
        }else{
            $this->_server = new swoole_server(
                '0.0.0.0',
                $serverPort,
                SWOOLE_PROCESS,
                $serverType==='tcp' ? SWOOLE_SOCK_TCP : SWOOLE_SOCK_UDP
            );
            $this->_server->on('Connect',      array($this, 'on_connect'));
            $this->_server->on('Receive',      array($this, 'on_receive'));
            $this->_server->on('Close',        array($this, 'on_close'));
        }

        // load config
        $setting = $this->config->get('setting', array());
        if( !empty($setting) ){
            $this->_server->set($setting);
        }

        // Setup callback function : Connect/Close/Receive are must
        $this->_server->on('Start',        array($this, 'on_start'));
        $this->_server->on('Shutdown',     array($this, 'on_shutdown'));
        $this->_server->on('Timer',        array($this, 'on_timer'));
        $this->_server->on('WorkerStart',  array($this, 'on_worker_start'));
        $this->_server->on('WorkerStop',   array($this, 'on_worker_stop'));
        $this->_server->on('Task',         array($this, 'on_task'));
        $this->_server->on('Finish',       array($this, 'on_finish'));
        $this->_server->on('WorkerError',  array($this, 'on_worker_error'));
        $this->_server->on('ManagerStart', array($this, 'on_mgr_start'));

        // Run server
        $this->_server->start();
        return true;
    }

    /**
     * on_start : callback function after started
     * @param  object $server server instance
     * @param  int $workerId worker id
     * @return string         result
     */
    public function on_start($server, $workerId = 0) {
        // log pids and port
        log_message('info', __METHOD__.'('.$server->master_pid.'/'.$server->manager_pid.').......@ '.$this->config->get('server_port', 2048).'(Ver '.swoole_version().')');

        // change user id and group
        // $user_name = $this->config->get('user_name', '_www');
        // if( !self::changeUser($user_name) ){
        //     log_message('error', 'Failed to change user');
        // }

        // set process name
        $proc_name = $this->config->get('process_name', 'my_server');
        if ($workerId >= $this->config->get('worker_num', 4)){
            self::setProcessName('php '.$proc_name.': task');
        }else{
            self::setProcessName('php '.$proc_name.': worker');
        }

        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);
    }

    /**
     * changeUser : Change current uid and gid
     * @param  string $user user name
     * @return boolean       Operation result
     */
    static function changeUser($user){
        if (!function_exists('posix_getpwnam')){
            trigger_error(__METHOD__.": require posix extension.");
            return false;
        }
        $user = posix_getpwnam($user);
        if($user){
            posix_setuid($user['uid']);
            posix_setgid($user['gid']);
            return true;
        }
        return false;
    }

    /**
     * setProcessName : set current process name
     * @param None
     */
    static function setProcessName($name){
        // if( function_exists('swoole_set_process_name') ){
        //     swoole_set_process_name($name);
        // }elseif( function_exists('cli_set_process_title') ){
        //     cli_set_process_title($name);
        // }else{
        //     trigger_error(__METHOD__." failed. require cli_set_process_title or swoole_set_process_name.");
        // }
    }

    /**
     * on_connect : callback function for worker after connected
     *
     * @param  object $server server instance
     * @param  object $fd     file descriptor of connection
     * @param  object $fromId from which poll process
     */
    public function on_connect($server, $fd, $fromId) {
        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);
    }

    /**
     * on_receive :  Callback function after received data
     *
     * @param  object $server server instance
     * @param  object $fd     file descriptor of connection
     * @param  object $fromId from which poll process
     * @param  string  $data  Received data
     */
    public function on_receive($server, $fd, $fromId, $data) {
        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);

        $key = trim($data);
        // stop or reload
        if ($key == 'swoole:shutdown') {
            $server->send($fd, 'server will be shutdown');
            $server->shutdown();
        } elseif ($key == 'swoole:reload') {
            $server->send($fd, 'server worker will be restart');
            $server->reload();
        } elseif ($key == 'swoole:check') {
            $server->send($fd, 'ok');
        } elseif ($key == 'swoole:stat') {
            $server->send($fd, json_encode($server->stats()));
        } else {
            // $start = microtime(1);
            // require self::_filePath('receive');
            // $now = @date('Y-m-d H:i');
            // if ($server->prof['now'] != $now) {
            //     $server->prof = self::_initProf($server);
            // }
            // $server->prof['c']++;
            // $server->prof['t'] += (microtime(1) - $start);
        }
    }

    /**
     * on_close : Callback function after the session has been closed
     * @param  object $server server instance
     * @param  object $fd     file descriptor of connection
     * @param  object $fromId from which poll process
     * @return None
     */
    public function on_close($server, $fd, $fromId) {
        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);
    }

    /**
     * on_shutdown : Callback function for shutdown
     * @param object $server server instance
     * @return None
     */
    public function on_shutdown($server) {
        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);
    }

    /**
     * on_timer : Callback function for timer
     * @param  object $server server instance
     * @param  int $interval  timer interval
     * @return None
     */
    public function on_timer($server, $interval) {
        // log pids and port
        log_message('info', __METHOD__.'.......@ '.$interval);

        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);
    }

    /**
     * on_worker_start : Callback function after worker started
     * @param  object $server   server instance
     * @param  int    $workerId worker id
     * @return None
     */
    public function on_worker_start($server, $workerId) {
        // change current folder to specified folder
        $old_dir = getcwd();
        $web_root = $this->config->get('http_web_root', '.');
        if( !empty($web_root) && '.'===$web_root ){
            if( !chdir($web_root) ){
                log_message('error', 'Failed to change to folder : '.$web_root);
            }
        }

        // load system plugins
        if( !$this->load_sys_plugins($workerId) ){
            log_message('error', 'Failed to load system plugins : '.$workerId);
        }

        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);

        // log workerId
        log_message('debug', __METHOD__.'.......'.$workerId);
    }

    /**
     * on_worker_stop : Callback function after worker stopped
     * @param  object $server   server instance
     * @param  int    $workerId worker id
     * @return None
     */
    public function on_worker_stop($server, $workerId) {
        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);

        // log workerId
        log_message('info', __METHOD__.'.......'.$workerId);

        // release system plugins
        if( !$this->release_sys_plugins($workerId) ){
            log_message('error', 'Failed to release system plugins');
        }
    }

    /**
     * on_task : Callback function after received task
     * @param  object $server   server instance
     * @param  string $taskId   task id, will be duplicated crossing different worker id
     * @param  object $fromId   from which poll process
     * @param  string $data     task parameters
     * @return None
     */
    public function on_task($server, $taskId, $fromId, $data) {
        log_message('info', __METHOD__." : taskId=$taskId, fromId=$fromId, data=$data");
        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);

        $blResult = true;
        try{
            // decode the request data
            $task_data = @json_decode($data,true);

            // sanity check
            if( !is_array($task_data) || !isset($task_data['class']) || !isset($task_data['function']) || !isset($task_data['params'])
                || empty($task_data['function']) ){
                throw new Exception('Invalid parameters in : '.__METHOD__);
            }

            // load class
            if( empty($task_data['class']) ){
                $task_data['class'] = 'base_task';
            }
            if( !class_exists($task_data['class'], false) ){
                $cls_file = $this->config->get('task_home' ,APP_PATH.'/models/task').'/'.$task_data['class'].'.php';
                if( !file_exists($cls_file) ){
                    throw new Exception('Invalid class has been provided : '.$task_data['class']);
                }
                require_once $cls_file;
            }

            // create instance
            $obj = new $task_data['class']();

            // call task function
            $func = $task_data['function'];
            if( empty($func) ){
                $func = 'process';
            }
            if( !method_exists($obj, $func) ){
                throw new Exception('Invalid function has been provided : '.$func);
            }
            return $obj->$func($task_data['params']);
        } catch(Exception $e) {
            log_message('error', $e->getMessage());
            $blResult = false;
        }

        return '';
    }

    /**
     * on_finish : Callback function after task has been finished
     * @param  object $server   server instance
     * @param  string $taskId   task id, will be duplicated crossing different worker id
     * @param  string $data     task parameters
     * @return None
     */
    public function on_finish($server, $taskId, $data) {
        log_message('info', __METHOD__." : taskId=$taskId, data=$data");
        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);
    }

    /**
     * on_worker_error : Callback function for exception on worker/task_worker
     * @param  object $server    server instance
     * @param  int    $workerId  worker id
     * @param  int    $workerPId worker process id
     * @param  int    $errCode   error code
     * @return None
     */
    public function on_worker_error($server, $workerId, $workerPId, $errCode) {
        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);

        log_message('error', 'Error Code : '.$errCode.' on ('.$workerId.'/'.$workerPId.')');
    }

    /**
     * on_mgr_start : Callback function after manager started
     * @param  object $server    server instance
     * @return None
     */
    public function on_mgr_start($server) {
        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);

        log_message('info', __METHOD__.'......');
    }

    /**
     * make_vars : prepare the system variables
     * @param  object &$output object container
     * @param  array $input    input array
     * @return None
     */
    public function make_vars(&$output, &$input){
        if( !is_array($input) || !is_array($output) ){
            return;
        }
        if( empty($output) )    $output = array();
        foreach ($input as $key => $value) {
            if( $key !== strtoupper($key) ){
                $new_key = strtoupper($key);
                $input[$new_key] = $value;
                unset($input[$key]);
                $key = $new_key;
            }
            $output[$key] = $value;
        }
    }

    /**
     * _set_global_vars : set global variables
     * @param object $request   request instance
     */
    private function _set_global_vars($request){
        $this->make_vars($_SERVER,  $request->server);
        $this->make_vars($_COOKIE,  $request->cookie);

        if( empty($request->server['REQUEST_METHOD']) ){
            $request->server['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }
        switch ($request->server['REQUEST_METHOD']) {
            case 'GET':
                $this->make_vars($_GET, property_exists($request, 'get') ? $request->get : array());
                $_POST = array();
                $_REQUEST = $_GET;
                break;
            case 'POST':
                $_GET = array();
                $this->make_vars($_POST, property_exists($request, 'post') ? $request->post : array());
                $_REQUEST = $_POST;
                break;
            default:
                $_REQUEST = array();
                break;
        }
        // TODO : prepare $_SESSION
        // $_SESSION = array();

        if( empty($_SERVER) )   $_SERVER = array();
        if( empty($_GET) )      $_GET    = array();
        if( empty($_POST) )     $_POST   = array();
        if( empty($_COOKIE) )   $_COOKIE = array();
        if( empty($_REQUEST) )  $_REQUEST= array();
        // if( empty($_SESSION) )  $_SESSION= array();
    }

    /**
     * on_request : Callback function after received HTTP request
     * @param  swoole_http_request  $request  request object
     * @param  swoole_http_response $response response object
     * @return None
     */
    public function on_request($request, $response){
        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);

        // set super global variable
        if( method_exists($request, 'setGlobal') ){
            $request->setGlobal(HTTP_GLOBAL_ALL);
        }else{
            $this->_set_global_vars($request);
        }

        // output access log
        log_message('info', (isset($request->server['REQUEST_METHOD']) ? $request->server['REQUEST_METHOD'] : 'GET').' '.(isset($request->server['REQUEST_URI']) ? $request->server['REQUEST_URI'] : '').'......');

        $blResult = true;
        // load plugins
        $app = new super_app( $this->load_http_plugins($request, $response) );

        // save app instance into current process's slot
        $this->_apps[$this->_server->worker_id] = $app;

        $http_code = 200;
        $time_cost = 0;
        try {
            // cache the current ouput buffer
            $app->buffer->cache_output();

            // output example
            // echo '<h1>'.$request->server['REQUEST_METHOD'].' '.$request->server['REQUEST_URI'].' : '.rand(1000, 9999).'</h1><hr />';

            // parse url
            $segment = $app->router->parse_uri(
                $request->server['REQUEST_URI'],
                $this->config->get('http_default_ctrl', 'base_ctrl'),
                $this->config->get('http_url_prefix', ''),
                $this->config->get('http_web_root', '.')
            );

            $time_start = microtime(true);

            // route the relevant class
            if( is_string($segment) ){
                // output directtly for the specified file
                $this->_server->sendfile($segment, $request->fd);
            }else{
                $cls_name = $app->router->fetch_class();
                $action_name = $app->router->fetch_action();

                // load class
                if( !$this->load_class($cls_name, 'controllers', true) ){
                    throw new Exception('Failed to load : '.$cls_name, 404);
                }

                // call to current action
                $http_code = $app->router->route();
            }

            $time_cost = 1000 * (microtime(true) - $time_start);

            // // change back to previous folder
            // if( !chdir($old_dir) ){
            //     throw new Exception('Failed to change back to folder : '.$old_dir, -5);
            // }
        } catch(Exception $e) {
            if( $e->getCode()>0 ){
                $http_code = $e->getCode();
            }else{
                $http_code = 500;
            }
            log_message('error', $e->getMessage());
            $blResult = false;
        }
        // flash all ouput buffer
        $content = $app->buffer->flash_output();

        // output access log
        log_message($http_code>=400 ? 'error' : 'info', sprintf('%s %s........%d (%04f ms) : %d B: %s : %s',
            $request->server['REQUEST_METHOD'],
            $request->server['REQUEST_URI'],
            $http_code,
            $time_cost,
            strlen($content),
            $request->server['REMOTE_ADDR'],
            $request->header['user-agent']
        ));

        // release the plugins
        $this->release_http_plugins($app->get_raw_data());
        unset($this->_apps[$this->_server->worker_id]);

        // finanlly, send out the response data to client
        $response->status($http_code);
        $response->end( $content );
    }

    /**
     * load_http_plugins : load http plugins
     * @param  object $request  instance object of request
     * @param  object $response instance object of response
     * @return array            list of loaded plugins
     */
    private function load_http_plugins($request, $response){
        $plugins = array();
        $cfg = $this->config->get('http_plugin', array());
        while( count($cfg)>0){
            $name = array_shift($cfg);
            $cls_name = 'plugin_'.$name;
            if (!class_exists($cls_name, false)) {
            // TODO : load class definition
            }
            $plugins[$name] = new $cls_name($request, $response);
            if( !$plugins[$name]->init() ){
                log_message('error', 'Failed to init plugin : '.$name);
            }
        }
        return $plugins;
    }

    /**
     * release_http_plugins : release plugins
     * @param  array &$plugins list of loaded plugins
     * @return None
     */
    public function release_http_plugins($plugins){
        $cfg = $this->config->get('http_plugin', array());
        while( count($cfg)>0){
            $name = array_pop($cfg);
            if( isset($plugins[$name]) ){
                if( !$plugins[$name]->uninit() ){
                    log_message('error', 'Failed to uninit plugin : '.$name);
                }
                unset($plugins[$name]);
            }
        }
    }

    /**
     * load_sys_plugins : load system plugins
     * @param  int $workerId     worker id
     * @return boolean            Operation result
     */
    private function load_sys_plugins($workerId){
        if( isset($this->_sys_plugins[$workerId]) ){
            return true;
        }
        $plugins = array();
        $cfg = $this->config->get('sys_plugin', array());
        $blResult = true;
        while( count($cfg)>0){
            $name = array_shift($cfg);
            $cls_name = 'plugin_'.$name;
            if (!class_exists($cls_name, false)) {
                require_once (APP_PATH.'/libs/'.$cls_name.'.php');
            }
            $plugins[$name] = new $cls_name();
            if( !$plugins[$name]->init() ){
                log_message('error', 'Failed to init plugin : '.$name);
                $blResult = false;
            }
        }
        $lock = new swoole_lock(SWOOLE_MUTEX);
        $lock->lock();
        $this->_sys_plugins[$workerId] = $plugins;
        $lock->unlock();

        return $blResult;
    }

    /**
     * release_sys_plugins : release system plugins
     * @param  int $workerId     worker id
     * @return boolean            Operation result
     */
    public function release_sys_plugins($workerId){
        if( !isset($this->_sys_plugins[$workerId]) ){
            return false;
        }

        $plugins = &$this->_sys_plugins[$workerId];
        $cfg = $this->config->get('sys_plugin', array());

        $lock = new swoole_lock(SWOOLE_MUTEX);
        $lock->lock();

        while( count($cfg)>0){
            $name = array_pop($cfg);
            if( isset($plugins[$name]) ){
                if( !$plugins[$name]->uninit() ){
                    log_message('error', 'Failed to uninit plugin : '.$name);
                }
                unset($plugins[$name]);
            }
        }

        $lock->unlock();
    }

    /**
     * get_plugins : get plugins for current working process
     * @return array       plugins for current working pocess
     */
    public function get_plugins(){
        $workerId = $this->_server->worker_id;
        return isset($this->_sys_plugins[$workerId]) ? $this->_sys_plugins[$workerId] : array();
    }

    /**
     * get_plugin : get plugin by name for current working process
     * @param  string  $plugin  plugin name
     * @return mixed       plugin instance or false if none can be found
     */
    public function get_plugin($plugin){
        $workerId = $this->_server->worker_id;
        return isset($this->_sys_plugins[$workerId]) && isset($this->_sys_plugins[$workerId][$plugin]) ? $this->_sys_plugins[$workerId][$plugin] : false;
    }

    /**
     * on_message : Callback function after received Websocket request
     * @param  swoole_http_request  $request  request object
     * @param  swoole_http_response $response response object
     * @return None
     */
    public function on_message($request, $response){
        // fire event
        $this->fire_event(__FUNCTION__, func_get_args(), false);
    }

    /**
     * fire_event : fire event
     * @param  string $event       event name
     * @param  array  $arrParams   event paramerters
     * @param  bool   $blLogEvent  flag to log event
     * @return none
     */
    private function fire_event($event, $arrParams=array(), $blLogEvent=true){
        if( $blLogEvent ){
            log_message('info', $event.'.......('.print_r($arrParams,true).')');
        }
    }

    /**
     * task : request task
     * @param  string    $class      class name
     * @param  string    $func       function name
     * @param  array     $task_data  task parameters
     * @param  boolean   $async      flag of sync or async
     * @return boolean         Operation result
     */
    public function task($class, $func, $task_data, $async=true){
        if( empty($this->_server) ){
            log_message('error', 'Failed to request task');
            return false;
        }
        $data = json_encode(array(
            'class'     => $class,
            'function'  => $func,
            'params'    => $task_data
        ));
        if( $async ){
            return $this->_server->task($data);
        }else{
            return $this->_server->taskwait($data);
        }
    }
}
