<?php

require SYS_PATH.'/controllers/utest.php';

class swale_tests extends utest{
    public function __construct($name=''){
        parent::__construct($name);
    }

    public function test_func1(){
        $this->_assert_true(1==0);
        $this->_assert_true(1==1);
        $this->_assert_true(1==1);
        // echo __METHOD__;
    }
}
