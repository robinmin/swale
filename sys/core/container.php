<?php

/**
 * std_container : Data container in memory
 */
class std_container{
    /**
     * $_container : actual container
     * @var array
     */
    protected $_container;

    /**
     * __construct : ctor
     * @param array $arrValues Initial values
     */
    public function __construct( $arrValues=array() ) {
        $this->_container = new stdClass();
        if( is_array($arrValues) || is_object($arrValues) ){
            foreach ($arrValues as $key => &$value) {
                $this->_container->$key = $value;
            }
        }
    }

    /**
     * __get : Magic method to retrieved data
     * @param  string $key key name
     * @return mixed      retrieved data value
     */
    public function __get($key) {
        return $this->get($key);
    }

    /**
     * get : get stored value by key
     * @param  string $key     key name
     * @param  mixed  $default default value
     * @return mixed          stored value
     */
    public function get($key, $default=NULL) {
        if( property_exists($this->_container, $key) ){
            return $this->_container->$key;
        }else{
            return $default;
        }
    }

    /**
     * __set : Magic method to set data into container
     * @param string $key   key name
     * @param mixed $value  value
     */
    public function __set($key, $value){
        $this->_container->$key = $value;
    }

    /**
     * get_raw_data : get raw container data
     * @return object     raw container data
     */
    public function get_raw_data(){
        return $this->_container;
    }

    /**
     * has : check current container has the named object or not
     * @param  string  $name object key name
     * @return boolean       has or not
     */
    public function has($name){
        return property_exists($this->_container, $name);
    }
}

/**
 * array_container : Data container in memory
 */
class array_container{
    /**
     * $_container : actual container
     * @var array
     */
    protected $_container;

    /**
     * __construct : ctor
     * @param array $arrValues Initial values
     */
    public function __construct( $arrValues=array() ) {
        $this->_container = array();
        if( is_array($arrValues) || is_object($arrValues) ){
            foreach ($arrValues as $key => &$value) {
                $this->_container[$key] = $value;
            }
        }
    }

    /**
     * __get : Magic method to retrieved data
     * @param  string $key key name
     * @return mixed      retrieved data value
     */
    public function __get($key) {
        return $this->get($key);
    }

    /**
     * get : get stored value by key
     * @param  string $key     key name
     * @param  mixed  $default default value
     * @return mixed          stored value
     */
    public function get($key, $default=NULL) {
        if( isset($this->_container[$key]) ){
            return $this->_container[$key];
        }else{
            return $default;
        }
    }

    /**
     * __set : Magic method to set data into container
     * @param string $key   key name
     * @param mixed $value  value
     */
    public function __set($key, $value){
        $this->_container[$key] = $value;
    }

    /**
     * get_raw_data : get raw container data
     * @return array     raw container data
     */
    public function get_raw_data(){
        return $this->_container;
    }

    /**
     * has : check current container has the named object or not
     * @param  string  $name object key name
     * @return boolean       has or not
     */
    public function has($name){
        return isset($this->_container[$name]);
    }
}


///////////////////////////////////////////////////////////////////////////////
   // public function test_conainer(){
   //      $loops = 100000;
   //      $time_start = microtime_float();
   //      $ct = new std_container(array('dsds'=>1, 'ddd'=>'xxx'));
   //      for ($i=0; $i < $loops; $i++) {
   //          $ct->ddd = 'dssdsdds';
   //          $m = $ct->dd;
   //          $n = $ct->ddd;
   //      }
   //      $time_end = microtime_float();
   //      $time = $time_end - $time_start;
   //      echo 'std_container('.$loops.') = '.$time.PHP_EOL;

   //      $time_start = microtime_float();
   //      $ct = new array_container(array('dsds'=>1, 'ddd'=>'xxx'));
   //      for ($i=0; $i < $loops; $i++) {
   //          $ct->ddd = 'dssdsdds';
   //          $m = $ct->dd;
   //          $n = $ct->ddd;
   //      }
   //      $time_end = microtime_float();
   //      $time = $time_end - $time_start;
   //      echo 'array_container('.$loops.') = '.$time.PHP_EOL;
   // }
///////////////////////////////////////////////////////////////////////////////
