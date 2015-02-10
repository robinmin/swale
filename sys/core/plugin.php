<?php

class plugin_base{
    protected $_request;
    protected $_response;

    public function __construct($request=null, $response=null){
        $this->_request = &$request;
        $this->_response = &$response;
    }

    public function __destruct(){}

    public function init(){
        return true;
    }

    public function uninit(){
        return true;
    }
}

class plugin_router extends plugin_base{
    /**
     * $_segments : url segments
     * @var array
     */
    private $_segments;

    /**
     * $_params : parameters to the action
     * @var array
     */
    private $_params;

    /**
     * $_class : class name of controller, contain the sub folder name
     * @var string
     */
    private $_class;

    /**
     * $_action : action name
     * @var string
     */
    private $_action;

    /**
     * parse_uri : Parse URL
     * @param  string $uri          url
     * @param  string $default_ctl  default controller
     * @param  string $url_prefix   url prefix
     * @param  string $web_root     folder of web root
     * @return mixed  return string with full path name if specified file exists; Otherwise return url segments(seperated by '/')
     */
    public function parse_uri($uri, $default_ctl, $url_prefix='', $web_root='.'){
        $this->_segments = array();
        $this->_params = array();
        $this->_class = '';
        $this->_action= '';

        $uri = preg_replace('/[\/\\\\]+/', '/', trim($uri));

        // check http url prefix
        if( !empty($url_prefix) ){
            if( $url_prefix !== substr($uri,0,strlen($url_prefix)) ){
                throw new Exception('Invalid HTTP prefix : '.$uri.' ('.$url_prefix.')', 404);
            }
        }

        // strip the prefix of url
        $real_url = substr($uri,strlen($url_prefix));
        $real_url = ltrim(rtrim($real_url,'/'),'/');
        if( !empty($web_root) && '.'!==$web_root ){
            $web_root .= '/';
        }else{
            $web_root = '';
        }

        if( empty($real_url) ){
            $this->_class = $default_ctl;
            $this->_action= 'index';
            return $this->_segments;
        }elseif( file_exists($web_root.$real_url) ){
            return $web_root.$real_url;
        }else{
            $this->_params = $this->_segments = explode('/', $real_url);

            // get class name and action name and parameters
            $cls_name = array_shift($this->_params);
            $action_name = array_shift($this->_params);
            while( is_dir(APP_PATH.'/controllers/'.$cls_name) ){
                $cls_name .= '/'.$action_name;
                $action_name = array_shift($this->_params);
            }
            if( empty($cls_name) ){
                $cls_name = $default_ctl;
            }
            if( empty($action_name) ){
                $action_name = 'index';
            }
            $this->_class = $cls_name;
            $this->_action= $action_name;

            return $this->_segments;
        }
    }

    /**
     * fetch_class : fetch current controller
     * @return string   class name of controller, contain the sub folder name
     */
    public function fetch_class(){
        return $this->_class;
    }

    /**
     * fetch_action : fetch current action
     * @return sting   action name
     */
    public function fetch_action(){
        return $this->_action;
    }

    /**
     * route : call the action defined in controller
     * @return int   http code
     */
    public function route(){
        if( empty($this->_class) || empty($this->_action) ){
            return 404;
        }
        // create instance of current instance
        $http_code = 200;
        $tmp_cls = basename($this->_class);
        $ctrl = new $tmp_cls();

        // call to current actio
        // is_callable() returns TRUE on some versions of PHP 5 for private and protected
        // methods, so we'll use this workaround for consistent behavior
        // if( in_array($this->_action, get_class_methods($tmp_cls)) ){
        if( method_exists($ctrl, $this->_action) ){
            $return = call_user_func_array(array($ctrl, $this->_action), $this->_params);
            if( is_int($return) ){
                $http_code = $return;
            }
        }else{
            throw new Exception('Invalid action has been provided => '.$this->_class.'::'.$this->_action, 404);
        }

        // setup http header
        $this->_response->header('Content-Type', empty($ctrl->content_type) ? 'text/html' : $ctrl->content_type);
        return $http_code;
    }

    /**
     * get_view_path : get view path for current class
     * @return None
     */
    public function get_view_path(){
        return APP_PATH.'/views/'.$this->_class;
    }
}

class plugin_buffer extends plugin_base{
    /**
     * $_old_buff : cached buffer before the action
     * @var string
     */
    private $_old_buff;

    /**
     * cache_output : cache buffer before the action
     * @param  boolean $cleanup ignore existing buffer
     * @return boolean           operation result
     */
    public function cache_output($cleanup=false){
        if( $cleanup ){
            $this->_old_buff = '';
        }else{
            // cache the current output buffer
            $this->_old_buff = @ob_get_contents();
            @ob_clean();
        }
        @ob_start();
        return true;
    }

    /**
     * flash_output : send the buffer to the client
     * @return int             send size
     */
    public function flash_output(){
        // output cached buffer
        if( empty($this->_old_buff) ){
            $this->_old_buff = '';
        }

        $strNew = $this->_old_buff.@ob_get_contents();
        @ob_clean();

        // clean up stored buffer
        $this->_old_buff = '';
        return $strNew;
    }
}

use Aura\Sql\ConnectionFactory;

class plugin_database extends plugin_base{
    /**
     * $_db : object container
     * @var object
     */
    private $_db = null;

    /**
     * init : connect to database
     * @return boolean     Operation result
     */
    public function init(){
        if( !empty($this->_db) ){
            return true;
        }

        // get system config
        $svr = AppServer::get_instance();
        $db_cfg = $svr->config->get('database', array());

        $result = true;
        try {
            $connection_factory = new ConnectionFactory;
            $db = $connection_factory->newInstance(
                isset($db_cfg['adapter']) ? $db_cfg['adapter'] : 'mysql',
                isset($db_cfg['dsn']) ? $db_cfg['dsn'] : 'host=localhost;dbname=mysql',
                isset($db_cfg['username']) ? $db_cfg['username'] : 'root',
                isset($db_cfg['password']) ? $db_cfg['password'] : ''
            );
            $db->connect();
            $this->_db = $db;
        } catch (PDOException $e) {
            $this->log_last_query();
            $result = false;
            log_message('error', 'Failed to connect to db server : '.$e->getMessage().' [ '.$e->getCode().' ]');
        }
        return $result;
    }

    /**
     * sql_read : read data from database by SQL statement
     * @param  string $sql    SQL statement
     * @param  mixed  $params value binded to the SQL statement
     * @return mixed          result array or false if any error encountered
     */
    public function sql_read($sql, $params=array()){
        if( empty($this->_db) ){
            return false;
        }

        $result = array();
        try {
            $result = $this->_db->fetchAssoc($sql, empty($params) ? array() : $params);
        } catch (PDOException $e) {
            $this->log_last_query();
            $result = false;
            log_message('error', 'Failed to run SQL statement : '.$e->getMessage().' [ '.$e->getCode().' ]');
        }
        return $result;
    }

    /**
     * sql_write : write data into database by SQL statement
     * @param  string $sql    SQL statement
     * @param  mixed  $params value binded to the SQL statement
     * @return mixed          #of rows effected or false if any error encountered
     */
    public function sql_write($sql, $params=array()){
        if( empty($this->_db) ){
            return false;
        }

        $result = 0;
        try {
            $stmt = $this->_db->query($sql, empty($params) ? array() : $params);
            $result = $stmt->rowCount();
        } catch (PDOException $e) {
            $this->log_last_query();
            $result = false;
            log_message('error', 'Failed to run SQL statement : '.$e->getMessage().' [ '.$e->getCode().' ]');
        }
        return $result;
    }

    /**
     *
     * Inserts a row of data into a table.
     *
     * @param string $table The table to insert into.
     *
     * @param array $cols An associative array where the key is the column
     * name and the value is the value to insert for that column.
     *
     * @return int The number of rows affected, typically 1.
     *
     */
    public function insert($table, array $cols){
        if( empty($this->_db) ){
            return false;
        }
        $result = 0;
        try {
            $result = $this->_db->insert($table, $cols);
        } catch (PDOException $e) {
            $this->log_last_query();
            $result = false;
            log_message('error', 'Failed to insert into '.$table.' : '.$e->getMessage().' [ '.$e->getCode().' ]');
        }
        return $result;
    }

    /**
     *
     * Updates a table with specified data based on WHERE conditions.
     *
     * @param string $table The table to update.
     *
     * @param array $cols An associative array where the key is the column
     * name and the value is the value to use for that column.
     *
     * @param string $cond Conditions for a WHERE clause.
     *
     * @param array $bind Additional data to bind to the query; these are not
     * part of the update, and note that the $cols values will take precedence
     * over these additional values.
     *
     * @return int The number of rows affected.
     *
     */
    public function update($table, array $cols, $cond, array $bind = array()){
        if( empty($this->_db) ){
            return false;
        }
        $result = 0;
        try {
            $result = $this->_db->update($table, $cols, $cond, $bind);
        } catch (PDOException $e) {
            $this->log_last_query();
            $result = false;
            log_message('error', 'Failed to update '.$table.' : '.$e->getMessage().' [ '.$e->getCode().' ]');
        }
        return $result;
    }

    /**
     *
     * Deletes rows from the table based on WHERE conditions.
     *
     * @param string $table The table to delete from.
     *
     * @param string $cond Conditions for a WHERE clause.
     *
     * @param array $bind Additional data to bind to the query.
     *
     * @return int The number of rows affected.
     *
     */
    public function delete($table, $cond, array $bind = array()){
        if( empty($this->_db) ){
            return false;
        }
        $result = 0;
        try {
            $result = $this->_db->delete($table, $cond, $bind);
        } catch (PDOException $e) {
            $this->log_last_query();
            $result = false;
            log_message('error', 'Failed to delete '.$table.' : '.$e->getMessage().' [ '.$e->getCode().' ]');
        }
        return $result;
    }

    /**
     * find_by : get the result by the condition array.
     *
     * @param  string $table    table name
     * @param  string $cond     condition
     * @param  mixed  $selected selected fields
     * @param  array  $bind     binded data
     * @access public
     * @return  array  result
     */
    public function find_by($table, $cond, $selected = '', $bind=array()) {
        if( empty($this->_db) ){
            return false;
        }
        $result = array();
        try {
            // create a new Select object
            $select = $this->_db->newSelect();
            $select->cols( empty($selected) ? array('*') : $selected)->from($table);
            if( !empty($cond) ){
                $select->where($cond);
            }
            $result = $this->_db->fetchAssoc($select, $bind);
        } catch (PDOException $e) {
            $this->log_last_query();
            $result = false;
            log_message('error', 'Failed to seek record by pk '.$table.' : '.$e->getMessage().' [ '.$e->getCode().' ]');
        }
        return $result;
    }

    /**
     * transBegin : start a transaction
     * @return boolean       Operation result
     */
    public function transBegin() {
        if( empty($this->_db) ){
            return false;
        }
        $result = true;
        try {
            // turn off autocommit and start a transaction
            $result = $this->_db->beginTransaction();
        } catch (PDOException $e) {
            $this->log_last_query();
            $result = false;
            log_message('error', 'Failed to start transaction : '.$e->getMessage().' [ '.$e->getCode().' ]');
        }
        return $result;
    }

    /**
     * transCommit : commit or rollback transaction.
     *
     * @param boolean $bSucc : status of transaction process
     * @access public
     * @return boolean       Operation result
     */
    public function transCommit($bSucc = true) {
        if( empty($this->_db) ){
            return false;
        }
        $result = true;
        try {
            if( $bSucc ){
                $result = $this->_db->commit();
            }else{
                $result = $this->_db->rollback();
            }
        } catch (PDOException $e) {
            $this->log_last_query();
            $result = false;
            log_message('error', 'Failed to start commit/rollback : '.$e->getMessage().' [ '.$e->getCode().' ]');
        }
        return $result;
    }

    /**
     * log_last_query : log last query SQL statment
     * @return None
     */
    public function log_last_query(){
        $query = &$this->_db->getProfiler()->getLastQuery();
        if( is_array($query) && isset($query['text']) ){
            // log_message('info', '[SQL] => '.preg_replace("/\r?\n/",' ',$query['text']));
            log_message('info', '[SQL] => '.$query['text']);
        }
    }
}

class plugin_session extends plugin_base{
    /**
     * $_sess_mgr : session manager
     * @var object
     */
    private $_sess_mgr = null;

    /**
     * $_sess_mgr : session manager
     * @var object
     */
    private $_sess_sgmt = null;

    /**
     * init : init function
     * @return boolean    operation result
     */
    public function init(){
        if( empty($this->_sess_mgr) ){
            $this->_sess_mgr = new \Aura\Session\Manager(
                new \Aura\Session\SegmentFactory,
                new \Aura\Session\CsrfTokenFactory(
                    new \Aura\Session\Randval(
                        new \Aura\Session\Phpfunc
                    )
                ),
                $_COOKIE
            );
            $this->_sess_sgmt = $this->_sess_mgr->newSegment(__FUNCTION__);   // set new segment

            // load session data by session cookie id into $_SESSION
        }
        return true;
    }

    /**
     * uninit : uninit function
     * @return boolean    operation result
     */
    public function uninit(){
        // save session data
        $sname = $this->_sess_mgr->getName();
        if( !empty($sname) ){
            $lifetime = 172800;
            $this->_response->cookie($sname, $this->_sess_mgr->getId(), time()+$lifetime);
        }
        return true;
    }

    /**
     * set : set session data
     * @param string $key   key name
     * @param mixed  $value value
     */
    public function set($key, $value){
        if( empty($this->_sess_sgmt) ){
            $this->init();
        }
        $result = $this->_sess_sgmt->$key = $value;
        return $result;
    }

    /**
     * get : get session data by key name
     * @param string $key   key name
     * @return mixed      value
     */
    public function get($key){
        if( empty($this->_sess_sgmt) ){
            $this->init();
        }
        return $this->_sess_sgmt->$key;
    }
}

class plugin_sessssdb extends plugin_base{
    /**
     * init : init function
     * @return boolean    operation result
     */
    public function init(){
        $svr = AppServer::get_instance();
        $option = isset($svr->config['cache']) ? $svr->config['cache'] : array();
        $ssdb = new SSDB\Client(
            isset($option['ip']) ? $option['ip'] : '127.0.0.1',
            isset($option['port']) ? $option['port'] : 8888
        );
        $handler = new SSDBSession\SessionHandler($ssdb);
        session_set_save_handler($handler, true);
        session_start();
    }

    /**
     * uninit : uninit function
     * @return boolean    operation result
     */
    public function uninit(){
    }
}
