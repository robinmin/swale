<?php

class MyTest{
    function run_test_job($params = array()){
        log_message('debug', json_encode($params));
        return __METHOD__;
    }
}
