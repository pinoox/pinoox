<?php

return [
    'default' => env('DB_CONNECTION', 'test'),
    
    'test' => [
        'driver' => env('DB_TEST_DRIVER', 'sqlite'),
        'database' => env('DB_TEST_DATABASE', ':memory:'),
        'prefix' => env('DB_TEST_PREFIX', ''),
    ],
    
    'development' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'pinoox'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', 'root'),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'collation' => env('DB_COLLATION', 'utf8mb4_bin'),
        'prefix' => env('DB_PREFIX', 'pincore_'),
        'strict' => env('DB_STRICT', true),
        'engine' => env('DB_ENGINE', null),
        'timezone' => env('DB_TIMEZONE', '+03:30'),
    ],
];

