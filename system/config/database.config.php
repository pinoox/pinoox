<?php

use Pinoox\Component\Database\DatabaseManager;

return [
    'default' => env('DB_CONNECTION', 'development'),

    'test' => [
        'driver' => env('DB_TEST_DRIVER', 'sqlite'),
        'database' => env('DB_TEST_DATABASE', ':memory:'),
        'prefix' => env('DB_TEST_PREFIX', ''),
    ],

    'development' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'pin'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', 'root'),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'collation' => env('DB_COLLATION', 'utf8mb4_bin'),
        'prefix' => env('DB_PREFIX', DatabaseManager::DEFAULT_CORE_TABLE_PREFIX),
        'strict' => env('DB_STRICT', true),
        'engine' => env('DB_ENGINE', null),
        'timezone' => env('DB_TIMEZONE', '+03:30'),
    ],

    'production' => [
        'driver' => env('DB_PROD_DRIVER', env('DB_DRIVER', 'mysql')),
        'host' => env('DB_PROD_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('DB_PROD_PORT', env('DB_PORT', '3306')),
        'database' => env('DB_PROD_DATABASE', env('DB_DATABASE', 'pinoox')),
        'username' => env('DB_PROD_USERNAME', env('DB_USERNAME', 'root')),
        'password' => env('DB_PROD_PASSWORD', env('DB_PASSWORD', 'root')),
        'charset' => env('DB_PROD_CHARSET', env('DB_CHARSET', 'utf8mb4')),
        'collation' => env('DB_PROD_COLLATION', env('DB_COLLATION', 'utf8mb4_bin')),
        'prefix' => env('DB_PROD_PREFIX', env('DB_PREFIX', DatabaseManager::DEFAULT_CORE_TABLE_PREFIX)),
        'strict' => env('DB_PROD_STRICT', env('DB_STRICT', true)),
        'engine' => env('DB_PROD_ENGINE', env('DB_ENGINE', null)),
        'timezone' => env('DB_PROD_TIMEZONE', env('DB_TIMEZONE', '+03:30')),
    ],

    'staging' => [
        'driver' => env('DB_STAGING_DRIVER', env('DB_DRIVER', 'mysql')),
        'host' => env('DB_STAGING_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('DB_STAGING_PORT', env('DB_PORT', '3306')),
        'database' => env('DB_STAGING_DATABASE', env('DB_DATABASE', 'pinoox')),
        'username' => env('DB_STAGING_USERNAME', env('DB_USERNAME', 'root')),
        'password' => env('DB_STAGING_PASSWORD', env('DB_PASSWORD', 'root')),
        'charset' => env('DB_STAGING_CHARSET', env('DB_CHARSET', 'utf8mb4')),
        'collation' => env('DB_STAGING_COLLATION', env('DB_COLLATION', 'utf8mb4_bin')),
        'prefix' => env('DB_STAGING_PREFIX', env('DB_PREFIX', DatabaseManager::DEFAULT_CORE_TABLE_PREFIX)),
        'strict' => env('DB_STAGING_STRICT', env('DB_STRICT', true)),
        'engine' => env('DB_STAGING_ENGINE', env('DB_ENGINE', null)),
        'timezone' => env('DB_STAGING_TIMEZONE', env('DB_TIMEZONE', '+03:30')),
    ],
];
