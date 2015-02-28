<?php

return [
    'server_type' => 'http',
    'server_port' => 2048,
    'setting' => [
        'worker_num'                => 4,
        'task_worker_num'           => 4,
        'daemonize'                 => false,
        'max_request'               => 10000,
        'dispatch_mode'             => 2,
        'debug_mode'                => 1,
        'open_tcp_keepalive'        => 1,
        'task_ipc_mode'             => 2,
        // 'log_file'                  => '/var/log/swale/swale_'.date('Ymd').'.log',
        'log_file'                  => '',
        'heartbeat_idle_time'       => 5,
        'heartbeat_check_interval'  => 5
    ],
    'database' => [
            'adapter'   => 'mysql',
            'dsn'       => 'host=localhost;dbname=test',
            'username'  => 'user',
            'password'  => 'password',
            'debug_mode'=> false
    ],
    'cache' => [
        'ip'   => '127.0.0.1',
        'port' => '8888'
    ],
    'http_plugin'       => [],
    'sys_plugin'        => [],
    'acl_on'            => true,
    'http_url_prefix'   => ''
];
