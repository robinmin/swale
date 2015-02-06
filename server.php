<?php

/*
 *---------------------------------------------------------------
 * SYS_ENV : Application environment
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 *
 */
define('SYS_ENV', 'development');

/**
 * SYS_PATH : Path name of the system
 */
if( file_exists(__DIR__.'/sys') ){
    define('SYS_PATH', __DIR__.'/sys');
}else{
    define('SYS_PATH', __DIR__.'/vendor/robinmin/swale/sys');
}
/**
 * APP_PATH : Path name of the current application
 */
define('APP_PATH', __DIR__.'/apps');

/**
 * Load necessary class definition to start the server
 */
require SYS_PATH.'/core/bootstrap.php';

/**
 * Start the Application server
 */
AppServer::get_instance([
])->start();
