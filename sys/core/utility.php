<?php

/**
 * log_message : log message
 * @param  string $level   log level
 * @param  string $message message
 * @return None
 */
function log_message($level, $message=''){
    $level = strtoupper($level);
    $begin = '';
    $end ='';
    switch( $level ){
        case 'ERROR':
            $begin = "\x1b[1;31;40m";
            $end   = "\x1b[0m";
            break;
        case 'INFO':
            $begin = "\x1b[1;32;40m";
            $end   = "\x1b[0m";
            break;
        default:
            break;
    }
    fwrite(STDERR, $begin.date('m-d H:i:s').' @'.getmypid().' : ['.$level.'] '.$message.$end.PHP_EOL);
}

function microtime_float(){
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * render : render template
 * @param  string  $template template name
 * @param  array  $data      template data
 * @param  boolean $return   display or return the rendered result
 * @return mixed         true/false or rendered result
 */
function render($template, $data, $return = FALSE){
    if( empty($template) ){
        return FALSE;
    }
    $smarty = new Smarty;

    // set default options
    //$smarty->force_compile= true;
    $smarty->debugging      = false;
    $smarty->caching        = true;
    $smarty->cache_lifetime = 120;
    $smarty->left_delimiter = '{';
    $smarty->right_delimiter= '}';

    $smarty->compile_dir = APP_PATH."/cache/templates_c";
    // $smarty->config_dir = APP_PATH."/configs";
    $smarty->cache_dir = APP_PATH."/cache";

    if( is_array($data) ){
        $smarty->assign($data);
    }
    // render
    return $smarty->fetch(
        $template,
        null,
        null,
        null,
        !$return
    );
}

/**
 * exception_handler : exception handler
 * @param  object $exception instance of exception
 * @return none
 */
function exception_handler($exception){
    $msg = '';
    try{
        // these are our templates
        $traceline = "#%s %s(%s): %s(%s)";
        $msg =<<<END_OF_CODE
PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s
Stack trace:
%s
  thrown in %s on line %s
END_OF_CODE;

        // alter your trace as you please, here
        $trace = $exception->getTrace();
        foreach ($trace as $key => $stackPoint) {
            // I'm converting arguments to their type
            // (prevents passwords from ever getting logged as anything other than 'string')
            $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
        }

        // build your tracelines
        $result = array();
        foreach ($trace as $key => $stackPoint) {
            $result[] = sprintf(
                $traceline,
                $key,
                $stackPoint['file'],
                $stackPoint['line'],
                $stackPoint['function'],
                implode(', ', $stackPoint['args'])
            );
        }
        // trace always ends with {main}
        $result[] = '#' . ++$key . ' {main}';

        // write tracelines into main template
        $msg = sprintf(
            $msg,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode(PHP_EOL, $result),
            $exception->getFile(),
            $exception->getLine()
        );

        // log the message
        log_message('error', $msg);
    } catch (Exception $e) {
        // if failed to process exception
        echo $msg;
        var_dump($e);
    }
}

/**
 * shutdown_handler : shutdown handler
 * @return none
 */
function shutdown_handler(){
    $msg = '';
    try{
        $error = error_get_last();
        if (!isset($error['type'])) return;
        switch ($error['type']){
            case E_ERROR :
            case E_PARSE :
            case E_DEPRECATED:
            case E_CORE_ERROR :
            case E_COMPILE_ERROR :
                break;
            default:
                return;
        }
        $msg = "{$error['message']} ({$error['file']}:{$error['line']})";
        log_message('error', $msg);
    } catch (Exception $e) {
        // if failed to process exception
        echo $msg;
        var_dump($e);
    }
}

/**
 * error_handler : error handler
 * @param  int    $errno   error code
 * @param  string $errstr  error message
 * @param  string $errfile error file
 * @param  int    $errline line number
 * @return none
 */
function error_handler($errno, $errstr, $errfile, $errline){
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }

    $msg = '';
    try{
        $msg .= "[$errno] $errstr".PHP_EOL;
        $msg .= "  Error on line $errline in file $errfile";
        $msg .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")".PHP_EOL;
        log_message('error', $msg);
    } catch (Exception $e) {
        // if failed to process exception
        echo $msg;
        var_dump($e);
    }

    return true;
}
