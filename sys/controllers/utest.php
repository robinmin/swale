<?php

class utest extends base_ctrl{
    protected $modelname;
    protected $modelname_short;

    // current method name
    protected $_method_name;

    // test results
    protected $_results;

    protected $message;
    protected $messages;
    protected $asserts;

    /**
     * __construct : ctor
     * @param string $name model name
     */
    public function __construct($name=''){
        parent::__construct();
        $this->modelname = $name;
        $this->modelname_short = basename($name, '.php');
        $this->messages = array();
        $this->_method_name = '';
    }

    /**
     * index : interface to run the unit test
     * @param  string $cli         flag of cli, possible value must be '1' or '0'
     * @param  string $method   function name
     * @return none
     */
    public function index($cli='0', $method = ''){
        if( empty($method) ){
            $this->_run_all();
        }else{
            $this->_run($method);
        }
        $this->_show_results($cli);
    }

    /**
     * _show_results : show unit test result
     * @param  string $cli         flag of cli, possible value must be '1' or '0'
     * @return none
     */
    protected function _show_results($cli='0'){
        $app = super_app::get_app();
        // prepare data for rendering
        $this->_data = array(
            'module'    => $this->modelname_short,
            'utest'     => $this->_results,
            'base_url'  => base_url().$app->router->fetch_class().'/'.$app->router->fetch_action().'/'.$cli.'/',
            'time_stamp'=> date('Y-m-d H:i:s')
        );
        // show result
        if( '1'==$cli ){
            $this->_data['cli_start_ok'] = "\x1b[1;32;40m";
            $this->_data['cli_start_ng'] = "\x1b[1;31;40m";
            $this->_data['cli_end'] = "\x1b[0m";
            render(SYS_PATH.'/views/'.__CLASS__.'/index_cli.tpl', $this->_data, false, 'text/plain');
        }else{
            render(SYS_PATH.'/views/'.__CLASS__.'/index.tpl', $this->_data, false);
        }
    }

    /**
     * _run_all : run all unit test
     * @return none
     */
    private function _run_all(){
        foreach ($this->_get_test_methods() as $method){
            $this->_run($method);
        }
    }

    /**
     * _run : run unit test for curent method
     * @param  string $method function name
     * @return boolean         operational result
     */
    private function _run($method){
        if( empty($method) ){
            return FALSE;
        }
        // Reset message from test
        $this->message = '';
        $this->_method_name = $method;
        if( !isset($this->_results[$this->_method_name]) ){
            $this->_results[$this->_method_name] = array(
                'results' => array(),
                'assert_good'  => 0,
                'assert_bad'   => 0,
                'assert_total' => 0,
                'time_cost'    => ''
            );
        }

        // Reset asserts
        $time_start = microtime_float();
        $this->asserts = TRUE;
        try{
            // Run cleanup method _pre
            $this->_pre();

            // Run test case (result will be in $this->asserts)
            $this->$method();

            // Run cleanup method _post
            $this->_post();
        } catch(Exception $err) {
            log_message("error", $err->getMessage());
            $this->message = $err->getMessage();
            $this->asserts = FALSE;
        }
        $this->messages[] = $this->message;
        $time_end = microtime_float();

        // summary test results
        $good = 0;
        $bad = 0;
        foreach ($this->_results[$this->_method_name]['results'] as &$assertion) {
            if( isset($assertion['result']) && $assertion['result'] == TRUE){
                $good++;
            }else{
                $bad++;
            }
        }
        $this->_results[$this->_method_name]['assert_good'] = $good;
        $this->_results[$this->_method_name]['assert_bad']  = $bad;
        $this->_results[$this->_method_name]['assert_total']= $good+$bad;
        $this->_results[$this->_method_name]['time_cost']   = number_format(100*($time_end - $time_start), 3).' ms';

        return $this->asserts;
    }

    /**
     * _get_test_methods : list all public method for current requested ctontroller
     * @return array function name list
     */
    private function _get_test_methods(){
        $app = super_app::get_app();
        $methods = get_class_methods($app->router->fetch_class());
        $testMethods = array();
        foreach ($methods as $method) {
            if (substr(strtolower($method), 0, 5) == 'test_') {
                $testMethods[] = $method;
            }
        }
        return $testMethods;
    }

    /**
     * Cleanup function that is run before each test case
     * Override this method in test classes!
     */
    public function _pre() { }

    /**
     * Cleanup function that is run after each test case
     * Override this method in test classes!
     */
    public function _post() { }

    private function _log_result($func, $result=true, $message=''){
        if( !empty($message) ){
            $this->message = $message;
        }
        $this->_results[$this->_method_name]['results'][] = [
            'function'  => $func,
            'result'    => $result,
            'message'   => $message
        ];
    }

    public function _fail($message = '') {
        $this->_log_result(__FUNCTION__, false, $message);
        $this->asserts = FALSE;
        return FALSE;
    }

    public function _assert_true($assertion, $message='') {
        if($assertion) {
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return TRUE;
        } else {
            $this->asserts = FALSE;
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return FALSE;
        }
    }

    public function _assert_false($assertion, $message='') {
        if($assertion) {
            $this->asserts = FALSE;
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return FALSE;
        } else {
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return TRUE;
        }
    }

    public function _assert_true_strict($assertion, $message='') {
        if($assertion === TRUE) {
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return TRUE;
        } else {
            $this->asserts = FALSE;
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return FALSE;
        }
    }

    public function _assert_false_strict($assertion, $message='') {
        if($assertion === FALSE) {
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return TRUE;
        } else {
            $this->asserts = FALSE;
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return FALSE;
        }
    }

    public function _assert_equals($base, $check, $message='') {
        if($base == $check) {
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return TRUE;
        } else {
            $this->asserts = FALSE;
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return FALSE;
        }
    }

    public function _assert_not_equals($base, $check, $message='') {
        if($base != $check) {
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return TRUE;
        } else {
            $this->asserts = FALSE;
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return FALSE;
        }
    }

    public function _assert_equals_strict($base, $check, $message='') {
        if($base === $check) {
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return TRUE;
        } else {
            $this->asserts = FALSE;
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return FALSE;
        }
    }

    public function _assert_not_equals_strict($base, $check, $message='') {
        if($base !== $check) {
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return TRUE;
        } else {
            $this->asserts = FALSE;
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return FALSE;
        }
    }

    public function _assert_empty($assertion, $message='') {
        if(empty($assertion)) {
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return TRUE;
        } else {
            $this->asserts = FALSE;
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return FALSE;
        }
    }

    public function _assert_not_empty($assertion, $message='') {
        if(!empty($assertion)) {
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return TRUE;
        } else {
            $this->asserts = FALSE;
            $this->_log_result(__FUNCTION__, $this->asserts, $message);
            return FALSE;
        }
    }
}
