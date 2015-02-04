<?php

/**
 * Module Description:
 *
 * rbmq : redis based message queue
 *
 * PHP versions 5
 *
 * LICENSE Declaration:
 *
 * @category   library
 * @package    model
 * @author     $Author: Robin Min $
 * @copyright  1997-2015 Geex Finance
 * @version    0.0.1
 */
/*****************************************************************************/
/**
 * rbmq : library for Redis Based Message Queue
 *
 * Example :
 *
 * @category   library
 * @package    model
 * @author     Robin Min
 * @version    Ver 0.1
 */


/******************************************************************************
 * Consumer interface for Redis Based Message Queue
 ******************************************************************************/
class Rbmq_Consumer{
    public function process($rbmq,$qname,$message){return true;}
    public function on_before_process($rbmq,$qname){return true;}
    public function on_after_process($rbmq,$qname){return true;}
}

/******************************************************************************
 * RBMQ : Redis Based Message Queue
 ******************************************************************************/
class rbmq {
    private $ip;            // server ip
    private $port;      // server port
    private $server;        // server object
    // private $workers;    // workers
    /**
     * __construct : ctor
     *
     * @access public
     */
    public function __construct($options=array()) {
        $this->server   = null;
        if(is_array($options)){
            $this->ip        = isset($options['ip']) ? $options['ip'] : '127.0.0.1';
            $this->port      = isset($options['port']) ? $options['port'] : '8888';
        }else{
            $this->ip        = '127.0.0.1';
            $this->port      = '8888';
        }
    }


    public function __destruct() {
        if($this->server){
            $this->server->close();
            $this->server = null;
        }
    }
    /**
     * init : connect to the ssdb server and create specified message queue
     *
     * @access public
     */
    public function init($qname,$max_retry=3) {
        if(empty($qname))   return false;
        if(empty($max_retry))   $max_retry = 3;
        if(!$this->server){
            // connect to SSDB server
            try{
                $this->server = new SSDB\SimpleClient($this->ip, $this->port);
            }catch(Exception $e){
                log_message('error',$e->getMessage());
                return false;
            }
        }
        // create queue counter and queue config
        // q:counter : a integer of message id
        // q:config  : a hash map to store queue cconfiguration
        // q:worker  : a list to store the worker process
         // q:msgid   : a list to store the existing message ids
         // q:msg     : a hash map key/value pair to store message
        if( !$this->server->incr('q:counter:'.$qname, 1) ){
            log_message('error','Failed to create queue counter : '.$qname);
            return false;
        }
         $arrConfig = array(
            'name' => $qname,
            'max_retry' => $max_retry,
            'enable' => 1
         );
        if( false === $this->server->multi_hset('q:config:'.$qname, $arrConfig) ){
            log_message('error','Failed to create queue config : '.$qname);
            return false;
        }
        return $this->enable($qname,true);
    }

    /**
     * enable enable or disable the current queue
     * @param  string  $qname   queue name
     * @param  boolean $enable  enable or disable
     * @return boolean  operation result
     */
    public function enable($qname,$enable=true){
        if($this->server){
            $this->server->hset('q:config:'.$qname, 'enable', $enable ? 1 : 0);
            return true;
        }
        log_message('error','Failed to enable queue : '.$qname);
        return false;
    }

    /**
     * is_enabled check current queue is enabled or disabled
     * @param  string  $qname   queue name
     * @return boolean  enabled for true, otherwise for false
     */
    public function is_enabled($qname){
        if($this->server && !empty($qname)){
            $enable = $this->server->hget('q:config:'.$qname, 'enable');
            return 1==$enable;
        }
        return false;
    }

    /**
     * register_consumer register current consumer into specified queue
     * @param  string $qname                 queue name
     * @param  string $class_consumer       class name of the consumer
     * @return boolean  operation result
     */
    public function register_consumer($qname,$class_consumer,$error_continue=true){
        if(!class_exists($class_consumer) ){
            $path = APPPATH . 'modules/cron/'.$class_consumer.'.php';
            log_message('debug','manually add ' . $path);
            include_once ($path);
        }
        if($this->server && !empty($qname) && !empty($class_consumer) && class_exists($class_consumer) ){
            if(!$this->server->zexists('q:worker:'.$qname, $class_consumer)){
                if(false == $this->server->zset('q:worker:'.$qname, $class_consumer,$error_continue ? 1 : 0)){
                    log_message('error','Failed to add consumer '.$class_consumer.' into queue : '.$qname);
                    return false;
                }
            }
            return true;
        }
        log_message('error','Failed to add consumer '.$class_consumer.' into queue : '.$qname);
        return false;
    }

    /**
     * unregister_consumer unregister current consumer from specified queue
     * @param  string $qname                 queue name
     * @param  string $class_consumer       class name of the consumer
     * @return boolean  operation result
     */
    public function unregister_consumer($qname,$class_consumer){
        if( $this->server && !empty($qname) && !empty($class_consumer) ){
            if(!$this->server->zdel('q:worker:'.$qname, $class_consumer)){
                log_message('error','Failed to remove consumer '.$class_consumer.' from queue : '.$qname);
                return false;
            }
            return true;
        }
        log_message('error','Failed to remove consumer '.$class_consumer.' from queue : '.$qname);
        return false;
    }

    /**
     * add_quick_msg : quick way to add messages into queue
     * @param string $qname : queue name
     * @param string $cmd   : command
     * @param string $key   : key name
     * @param string $val   : value
     * @return mixed     return false in case any error encounter, otherwise return the message id added into curren queue
     */
    public function add_quick_msg($qname,$cmd,$key,$val){
        return $this->add_message($qname,array('cmd'=>$cmd,'key'=>$key,'val'=>$val));
    }

    /**
     * add_message      : add the message into current queue
     * @param string $qname      queue name
     * @param mixed  $message    message data
     * @return mixed     return false in case any error encounter, otherwise return the message id added into curren queue
     */
    public function add_message($qname,$message){
        if($this->server && !empty($qname) && !empty($message)){
            if( !$this->is_enabled($qname) )    return false;
            if( !is_array($message) ) $message = array($message);

            // get message ID
            $msgid = $this->server->incr('q:counter:'.$qname);
            if(false===$msgid){
                log_message('error','Failed to get new message id in '.$qname);
                return false;
            }
            // Set the data into hash map
            if(!$this->server->multi_hset('q:msg:'.$qname.':'.$msgid, $message)){
                log_message('error','Failed to append new message into queue '.$qname);
                return false;
            }

            // push the id into the queue...
            if(!$this->server->qpush('q:msgid:'.$qname, $msgid)){
                $this->server->multi_hdel('q:msg:'.$qname.':'.$msgid, array_keys($message));
                log_message('error','Failed to save new message id into queue '.$qname);
                return false;
            }
            return $msgid;
        }
        log_message('error','Failed to add message into queue : '.$qname);
        return false;
    }

    /**
     * purge_message get & purge one message from a specified queue
     * @param string $qname : queue name
     * @param string $type :  queue type, it can be waiting, failed, done
     * @return boolean  operation result
     */
    public function purge_message($qname,$type='waiting'){
        // get queue name by type
        $msg_name = 'msg';
        $msgid_name = 'msgid';
        if('failed'==$type){
            $msg_name = 'failed';
            $msgid_name = 'failedid';
        }elseif('done'==$type){
            $msg_name = 'done';
            $msgid_name = 'doneid';
        }

        if($this->server && !empty($qname)){
            if( !$this->is_enabled($qname) )    return false;
            $msgid = $this->server->qpop('q:'.$msgid_name.':'.$qname);
            if(false===$msgid){
                log_message('error','Failed to get message id '.$msgid.' from queue '.$qname);
                return false;
            }elseif(empty($msgid)){
                // return if no message in queue
                return array();
            }
            $message = $this->server->hgetall('q:'.$msg_name.':'.$qname.':'.$msgid);
            if(!$message){
                log_message('error','Failed to get all data by message id '.$msgid.' from queue '.$qname);
                return false;
            }
            $arrNew = array();
            for ($i=0; $i < count($message); $i+=2) {
                $arrNew[$message[$i]] = $message[$i+1];
            }
            $message = $arrNew;
            unset($arrNew);

            // delete the raw message
            if(!$this->server->multi_hdel('q:'.$msg_name.':'.$qname.':'.$msgid,array_keys($message))){
                log_message('error','Failed to purge message by msgid : '.$msgid .' on queue '.$qname);
                return false;
            }
            return $message;
        }
        log_message('error','Failed to purge message from queue : '.$qname);
        return false;
    }

    /**
     * purge_all purge all data
     * @param  string $qname
     * @param string $type :  queue type, it can be waiting, failed, done
     * @return boolean  operation result
     */
    public function purge_all($qname,$type='waiting'){
            // get queue name by type
            $msg_name = 'msg';
            $msgid_name = 'msgid';
            if('failed'==$type){
                $msg_name = 'failed';
                $msgid_name = 'failedid';
            }elseif('done'==$type){
                $msg_name = 'done';
                $msgid_name = 'doneid';
            }

            if($this->server && !empty($qname)){
            $blResult = true;
            $strMsgId = 'q:'.$msgid_name.':'.$qname;
            while($this->server->qsize($strMsgId)>0){
                $msgid = $this->server->qpop($strMsgId);
                if(false !== $msgid){
                    if(!$this->server->hclear('q:'.$msg_name.':'.$qname.':'.$msgid)){
                        log_message('error','Failed to purge all message on queue '.$qname);
                        $blResult = false;
                    }
                }
            }
            return $blResult;
        }
        log_message('error','Failed to purge all message from queue : '.$qname);
        return false;
    }

    /**
     * length : get length of current queue
     * @param  string $qname    queue name
     * @param string $type :  queue type, it can be waiting, failed, done
     * @return int  return the length of current queue.
     */
    public function length($qname,$type='waiting'){
        if($this->server && !empty($qname)){
            // get queue name by type
            $msg_name = 'msg';
            $msgid_name = 'msgid';
            if('failed'==$type){
                $msg_name = 'failed';
                $msgid_name = 'failedid';
            }elseif('done'==$type){
                $msg_name = 'done';
                $msgid_name = 'doneid';
            }
            return $this->server->qsize('q:'.$msgid_name.':'.$qname);
        }
        return 0;
    }

    public function run($qname,$one_off=true){
        if(empty($qname)){
            log_message('error','Invalid parames in run : '.$qname);
            return false;
        }
        if(!$this->server){
            log_message('error','Failed to connect to message queue : '.$this->ip.':'.$this->port);
            return false;
        }
        if( !$this->is_enabled($qname) )    return false;
        // get config
        $arrConfig = $this->server->hgetall('q:config:'.$qname);
        if( empty($arrConfig) ){
            log_message('error','Failed to get queue config : '.$qname);
            return false;
        }
        $arrNew = array();
        for ($i=0; $i < count($arrConfig); $i+=2) {
            $arrNew[$arrConfig[$i]] = $arrConfig[$i+1];
        }
        $arrConfig = $arrNew;
        unset($arrNew);

        if(!isset($arrConfig['max_retry'])){
            $arrConfig['max_retry'] = 3;
        }
        // build workers
        $workers = $this->setup_consumers($qname);
        if( false===$workers ){
            log_message('error','Failed to get queue consumers : '.$qname);
            return false;
        }

        // long loop to process the messages
        $blResult = true;
        while($blResult){
            // pop the message ID from the queue
            $msgid = $this->server->qpop('q:msgid:'.$qname);
            if(false===$msgid){
                log_message('error','Failed to get message id '.$msgid.' from queue '.$qname);
                $blResult = false;
                continue;
            }
            if(!empty($msgid)){
                // Get the message itself...
                $message = $this->server->hgetall('q:msg:'.$qname.':'.$msgid);
                if(!$message){
                    log_message('error','Failed to get all data by message id '.$msgid.' from queue '.$qname);
                    $blResult = false;
                    continue;
                }
                $arrNew = array();
                for ($i=0; $i < count($message); $i+=2) {
                    $arrNew[$message[$i]] = $message[$i+1];
                }
                $message = $arrNew;
                unset($arrNew);

                // delete the raw message
                if(!$this->server->multi_hdel('q:msg:'.$qname.':'.$msgid,array_keys($message))){
                    log_message('error','Failed to purge message by msgid : '.$msgid .' on queue '.$qname);
                    $blResult = false;
                    continue;
                }

                // Process the message...
                if($this->process_all($qname,$workers,$message)){
                    // move the processed message into done queue
                    $blMsg = $this->server->multi_hset('q:done:'.$qname.':'.$msgid, $message);
                    $blMsgID = $this->server->qpush('q:doneid:'.$qname, $msgid);
                    if(!$blMsg || !$blMsgID){
                        log_message('error','Failed to move message into done queue of '.$qname);
                        $blResult = false;
                        continue;
                    }
                }else{
                    // check the retry times biger thank the threshold or not
                    $times = $this->server->incr('q:retry:'.$qname.':'.$msgid);
                    if($times>=$arrConfig['max_retry']){
                        $blResult = false;
                        // move the message to the failed queue
                        $blMsg = $this->server->multi_hset('q:failed:'.$qname.':'.$msgid, $message);
                        $blMsgID = $this->server->qpush('q:failedid:'.$qname, $msgid);
                        if(!$blMsg || !$blMsgID){
                            log_message('error','Failed to move message into failed queue of '.$qname);
                            $blResult = false;
                            continue;
                        }
                    }else{
                        // add the message back to the normal queue
                        $blMsg = $this->server->multi_hset('q:msg:'.$qname.':'.$msgid, $message);
                        $blMsgID = $this->server->qpush('q:msgid:'.$qname, $msgid);
                        if(!$blMsg || !$blMsgID){
                            log_message('error','Failed to add back the message into waiting queue of '.$qname);
                            $blResult = false;
                            continue;
                        }
                    }
                }
            }
            if($this->length($qname)>0)   continue;   // ensure diegest all

            // quit for the one off job consumer
            if($one_off)    break;

            sleep(60);  // sleep for next round
        }
        if(!$this->teardown_consumers($qname,$workers)){
            $blResult = false;
        }
        return $blResult;
    }

    /**
     * setup_consumers : create all object for consumer workers and call on_before_process on each object
     * @param  string $qname    queue name
     * @return mixed    object array or return false in case of any error encountered
     */
    private function setup_consumers($qname){
        // build workers
        $clsWkrs = $this->server->zrange('q:worker:'.$qname,0,-1);
        if( empty($clsWkrs) ){
            log_message('error','Failed to get queue consumers : '.$qname);
            return false;
        }
        $wkrs = array();
        $clsWkrs = array_reverse($clsWkrs); // to make sure the call sequence
        foreach ($clsWkrs as $clsWkr => $nIdx) {
            try{
                $wkrs[$clsWkr] = new $clsWkr();
                if(!$wkrs[$clsWkr]->on_before_process($this,$qname)){
                    log_message('error','Failed to call on_before_process on queue '.$qname);
                }
            }catch(Exception $e){
                log_message('error',$e->getMessage());
                return false;
            }
        }
        return $wkrs;
    }

    /**
     * teardown_consumers : call on_after_process on each object
     * @param  string $qname    queue name
     * @param  array $wkrs      object array
     * @return boolean  operation result
     */
    private function teardown_consumers($qname,$wkrs){
        // call on_after_process in the reveal sequnce
        for ($i=count($wkrs); $i >0 ; $i--) {
            try{
                $arrTemp = array_keys($wkrs);
                $obj = $wkrs[ $arrTemp[$i-1] ];
                if(!$obj->on_after_process($this,$qname)){
                    log_message('error','Failed to call on_after_process on queue '.$qname);
                }
            }catch(Exception $e){
                log_message('error',$e->getMessage());
                return false;
            }
        }
        return true;
    }

    /**
     * process_all : call process on each object
     * @param  string $qname    queue name
     * @param  array  $wkrs     object array
     * @param  string $message  message data
     * @return boolean  operation result
     */
    private function process_all($qname,$wkrs,$message){
        $clsWkrs = $this->server->zrange('q:worker:'.$qname,0,-1);
        if( empty($clsWkrs) ){
            log_message('error','Failed to get queue consumers : '.$qname);
            return false;
        }
        $blResult = true;
        foreach ($wkrs as $clsWkr => $objWkr) {
            try{
                log_message('debug',"Processing queue $qname in $clsWkr");
                if(!$objWkr->process($this,$qname,$message)){
                    $blResult = false;
                    log_message('error','Failed to call process in '.$clsWkr);
                    if(!is_array($clsWkrs) || !isset($clsWkrs[$clsWkr]) || $clsWkrs[$clsWkr] !=1){
                        log_message('error','Stop the consumer loop duee to the configuration of '.$clsWkr.' stoped it on queue '.$qname.'.');
                        break;
                    }
                }
            }catch(Exception $e){
                log_message('error',$e->getMessage());
                return false;
            }
        }
        return $blResult;
    }
}
