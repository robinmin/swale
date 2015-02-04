<?php

class MyTest{
    function run_test_job($params = array()){
        log_message('debug', print_r($params,true));
        return __METHOD__;
    }
}
