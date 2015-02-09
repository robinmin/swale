<?php

class swale_tests extends utest{
    public function __construct($name=''){
        parent::__construct($name);
    }

    public function index(){
        echo __METHOD__;
    }
}
