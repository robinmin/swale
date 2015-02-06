<?php

/**
 * base_ctrl : class definition of base_ctrl
 */
class base_ctrl{
    /**
     * $_data : page data
     * @var array
     */
    protected $_data = array();

    public function __construct(){}
    public function __destruct(){}

    public function index(){
        return 200;
    }

    /**
     * get_model : get instance of specified model
     * @param  string $model  model name
     * @param  mixed  $params parameter for new model ctor
     * @return object         instance of model
     */
    public function get_model($model='', $params=null){
        $app = super_app::get_app();
        return $app->get_model($model, $params);
    }

    /**
     * render : render template
     * @param  string  $template template name
     * @param  array  $data      template data
     * @param  boolean $return   display or return the rendered result
     * @return mixed         true/false or rendered result
     */
    public function render($template = null, $data = null, $return = FALSE){
        $app = super_app::get_app();
        return render(
            $app->router->get_view_path().'/'.(empty($template) ? $app->router->fetch_action().'.tpl' : $template),
            empty($data) ? $this->_data : $data,
            $return
        );
    }
}

/**
 * base_model : class definition of base_model
 */
class base_model{
    /**
     * $_database : instance of database
     * @var object
     */
    private $_database = null;

    /**
     * __construct : ctor
     */
    public function __construct(){
        $app = super_app::get_app();
        $this->_database = &$app->database;
        if( false===$this->_database ){
            $this->_database = null;
        }
    }

    /**
     * __destruct : dector
     */
    public function __destruct(){}

    /**
     * sql_read : read data from database by SQL statement
     * @param  string $sql    SQL statement
     * @param  mixed  $params value binded to the SQL statement
     * @return array          result
     */
    public function sql_read($sql, $params=array()){
        if( empty($this->_database) ){
            return false;
        }
        return $this->_database->sql_read($sql, $params);
    }

    /**
     * sql_write : write data into database by SQL statement
     * @param  string $sql    SQL statement
     * @param  mixed  $params value binded to the SQL statement
     * @return mixed          #of rows effected or false if any error encountered
     */
    public function sql_write($sql, $params=array()){
        if( empty($this->_database) ){
            return false;
        }
        return $this->_database->sql_write($sql, $params);
    }

    public function insert($table, array $cols){
        if( empty($this->_database) ){
            return false;
        }
        return $this->_database->insert($table, $cols);
    }

    public function update($table, array $cols, $cond, array $bind = array()){
        if( empty($this->_database) ){
            return false;
        }
        return $this->_database->update($table, $cols, $cond, $bind);
    }

    public function delete($table, $cond, array $bind = array()){
        if( empty($this->_database) ){
            return false;
        }
        return $this->_database->delete($table, $cond, $bind);
    }

    public function find_by($table, $cond, $selected = '', $bind=array()) {
        if( empty($this->_database) ){
            return false;
        }
        return $this->_database->find_by($table, $cond, $selected, $bind);
    }

///////////////////////////////////////////////////////////////////////////////
// Code copied from existing projects
///////////////////////////////////////////////////////////////////////////////
    /**
     * executeReadSql : query sql.
     *
     * @param $sql SQL
     * @access public
     * @return  mixed query sql result or false when some error encountered
     */
    public function executeReadSql($sql, $params=array()) {
        return $this->sql_read($sql, $params);
    }

    /**
     * executeWriteSql : query sql.
     *
     * @param $sql SQL
     * @access public
     * @return  array() query sql result
     */
    public function executeWriteSql($sql, $params=array()) {
        return $this->sql_write($sql, $params);
    }

    /**
     * transBegin : start a transaction
     * @return None
     */
    public function transBegin() {
        if( empty($app->database) ){
            return false;
        }
        return $this->_database->transBegin();
    }

    /**
     * transCommit : commit or rollback transaction.
     *
     * @param boolean $bSucc : status of transaction process
     * @access public
     * @return boolean
     */
    public function transCommit($bSucc = true) {
        if( empty($app->database) ){
            return false;
        }
        return $this->_database->transCommit($bSucc);
    }
}

/**
 * base_task : base class of task
 */
class base_task{
    public function process($params){return '';}
}

/**
 * super_app : class definition of application
 */
class super_app extends array_container{
        /**
     * get_app : get app instance for current worker process
     * @return object  app instance
     */
    public static function get_app(){
        return AppServer::get_instance()->get_app();
    }

    /**
     * get_model : get instance of specified model
     * @param  string $model  model name
     * @param  mixed  $params parameter for new model ctor
     * @return object         instance of model
     */
    public function get_model($model='', $params=null){
        if( empty($model) ){
            $model = 'base_model';
        }

        $new_model = basename($model);
        // if( !class_exists($new_model, false) ){
        //     $cls_file = APP_PATH.'/models/'.$model.'.php';
        //     if( !file_exists($cls_file) ){
        //         return false;
        //     }
        //     require_once $cls_file;
        // }
        if( !AppServer::load_class($model, 'models', true) ){
            throw new Exception('Failed to load : '.$model, 404);
        }

        // create instance
        $obj = new $new_model($params);
        return $obj;
    }

    /**
     * __get : get plugin by name for current working process
     * @param  string  $plugin  plugin name
     * @return mixed       plugin instance or false if none can be found
     */
    public function __get($plugin){
        // if can find the plugin in sys_plugin list
        $plgn = AppServer::get_instance()->get_plugin($plugin);
        if( $plgn===false ){
            // if can find the plugin in http_plugin list
            return parent::__get($plugin);
        }
        return $plgn;
    }
}
