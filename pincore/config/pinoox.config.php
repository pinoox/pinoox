<?php

return [
    'version_code' => 19,
    'version_name' => '2.1.0',
    'apps_location' => 'apps',
    'lang' => 'en',
    'mode' => 'development', //development | production | test
    'log' => [
        'path' => sys_get_temp_dir() . '/pinoox.log',
        'channel' => 'app',
    ],
];
