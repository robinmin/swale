<?php

class utest extends base_ctrl{
    // // The folder INSIDE /controllers/ where the test classes are located
    // protected $test_dir = '/test/';

    protected $modelname;
    protected $modelname_short;

    // current method name
    protected $_method_name;

    // unit test result
    protected $_results;

    // protected $message;
    // protected $messages;
    protected $asserts;

    public function __construct($name=''){
        parent::__construct();
        $this->modelname = $name;
        $this->modelname_short = basename($name, '.php');
        $this->messages = array();
        $this->_method_name = '';
        $this->_results     = array();
    //     $this->load->library('unit_test');
    }

    public function index($method = ''){
        if( empty($method) ){
            $this->_run($method);
        }else{
            $this->_run_all();
        }

        // show result
        var_dump($this->_results);
        // return $this->_show_all();
    }

    // function show_results()
    // {
    //     $this->_run_all();
    //     $data['modelname'] = $this->modelname;
    //     $data['results'] = $this->unit->result();
    //     $data['messages'] = $this->messages;
    //     $this->load->view('test/results', $data);
    // }

    // private function _show_all(){
    //     $this->_run_all();
    //     $data['modelname'] = $this->modelname;
    // //     $data['results'] = $this->unit->result();
    //     $data['messages'] = $this->messages;

    //     $this->load->view('test/header');
    //     $this->load->view('test/results', $data);
    //     $this->load->view('test/footer');
    // }

    // private function _show($method){
    //     $this->_run($method);
    //     $data['modelname'] = $this->modelname;
    //     // $data['results'] = $this->unit->result();
    //     $data['messages'] = $this->messages;

    //     // $this->load->view('test/header');
    //     // $this->load->view('test/results', $data);
    //     // $this->load->view('test/footer');
    // }

    private function _run_all(){
        foreach ($this->_get_test_methods() as $method){
            $this->_run($method);
        }
    }

    private function _run($method){
        // Reset message from test
        $this->message = '';
        $this->_method_name = $method;
        if( !isset($this->_results[$this->_method_name]) ){
            $this->_results[$this->_method_name] = array();
        }

        // Reset asserts
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

        return $this->asserts;

    //     // Set test description to "model name -> method name" with links
    //     $this->load->helper('url');
    //     $test_class_segments = $this->test_dir . strtolower($this->modelname_short);
    //     $test_method_segments = $test_class_segments . '/' . substr($method, 5);
    //     $desc = anchor($test_class_segments, $this->modelname_short) . ' -> ' . anchor($test_method_segments, substr($method, 5));

    //     // Pass the test case to CodeIgniter
    //     $this->unit->run($this->asserts, TRUE, $desc);
    }

    private function _get_test_methods(){
        $methods = get_class_methods($this);
        $testMethods = array();
        foreach ($methods as $method) {
            if (substr(strtolower($method), 0, 5) == 'test_') {
                $testMethods[] = $method;
            }
        }
        return $testMethods;
    }

    // /**
    //  * Remap function (CI magic function)
    //  *
    //  * Reroutes any request that matches a test function in the subclass
    //  * to the _show() function.
    //  *
    //  * This makes it possible to request /my_test_class/my_test_function
    //  * to test just that single function, and /my_test_class to test all the
    //  * functions in the class.
    //  *
    //  */
    // function _remap($method)
    // {
    //     $test_name = 'test_' . $method;
    //     if (method_exists($this, $test_name))
    //     {
    //         $this->_show($test_name);
    //     }
    //     else
    //     {
    //         $this->$method();
    //     }
    // }

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
        $this->_results[$this->_method_name][] = [
            'function'  => $func,
            'result'    => $result,
            'message'   => $message
        ];
    }

    public function _fail($message = null) {
        $this->_log_result(__FUNCTION__, false, $message);
        $this->asserts = FALSE;
        if ($message != null) {
            $this->message = $message;
        }
        return FALSE;
    }

    public function _assert_true($assertion) {
        $this->_log_result(__FUNCTION__, $assertion);
        if($assertion) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function _assert_false($assertion) {
        $this->_log_result(__FUNCTION__, $assertion);
        if($assertion) {
            $this->asserts = FALSE;
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function _assert_true_strict($assertion) {
        $this->_log_result(__FUNCTION__, $assertion === TRUE);
        if($assertion === TRUE) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function _assert_false_strict($assertion) {
        $this->_log_result(__FUNCTION__, $assertion===FALSE ? FALSE : TRUE);
        if($assertion === FALSE) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function _assert_equals($base, $check) {
        $this->_log_result(__FUNCTION__, $base == $check);
        if($base == $check) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function _assert_not_equals($base, $check) {
        $this->_log_result(__FUNCTION__, $base != $check);
        if($base != $check) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function _assert_equals_strict($base, $check) {
        $this->_log_result(__FUNCTION__, $base === $check);
        if($base === $check) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function _assert_not_equals_strict($base, $check) {
        $this->_log_result(__FUNCTION__, $base !== $check);
        if($base !== $check) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function _assert_empty($assertion) {
        $this->_log_result(__FUNCTION__, empty($assertion));
        if(empty($assertion)) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function _assert_not_empty($assertion) {
        $this->_log_result(__FUNCTION__, !empty($assertion));
        if(!empty($assertion)) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }
}
