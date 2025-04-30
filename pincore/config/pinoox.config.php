<?php

return [
    'version_code' => 19,
    'version_name' => '2.1.0',
    'apps_location' => 'apps',
    'lang' => 'en',
    'mode' => 'test', //development | production | test
    'cache' => [
        'driver'             => 'symfony',     // symfony | pinker
        'directory'          => sys_get_temp_dir(),
        'default_namespace'  => 'app_cache_',
        'default_lifetime'   => 3600,          // seconds
    ],
    'log' => [
        'path' => sys_get_temp_dir() . '/pinoox.log',
        'channel' => 'app',
        'level' => \Monolog\Logger::DEBUG,
    ],
];
