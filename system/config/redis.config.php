<?php

return [
    'default' => env('REDIS_CONNECTION', env('PINOOX_REDIS_CONNECTION', 'default')),

    'prefix' => env('REDIS_PREFIX', env('PINOOX_REDIS_PREFIX', 'pinoox:')),

    'connections' => [
        'default' => [
            'driver' => env('REDIS_CLIENT', env('PINOOX_REDIS_CLIENT', 'phpredis')),
            'host' => env('REDIS_HOST', env('PINOOX_REDIS_HOST', '127.0.0.1')),
            'port' => (int) env('REDIS_PORT', env('PINOOX_REDIS_PORT', 6379)),
            'password' => env('REDIS_PASSWORD', env('PINOOX_REDIS_PASSWORD')),
            'database' => (int) env('REDIS_DB', env('PINOOX_REDIS_DB', 0)),
            'timeout' => (float) env('REDIS_TIMEOUT', env('PINOOX_REDIS_TIMEOUT', 1.0)),
            'read_timeout' => (float) env('REDIS_READ_TIMEOUT', env('PINOOX_REDIS_READ_TIMEOUT', 1.0)),
            'persistent' => (bool) env('REDIS_PERSISTENT', env('PINOOX_REDIS_PERSISTENT', false)),
            'prefix' => env('REDIS_PREFIX', env('PINOOX_REDIS_PREFIX', 'pinoox:')),
        ],

        'cache' => [
            'driver' => env('REDIS_CACHE_CLIENT', env('REDIS_CLIENT', 'phpredis')),
            'host' => env('REDIS_CACHE_HOST', env('REDIS_HOST', '127.0.0.1')),
            'port' => (int) env('REDIS_CACHE_PORT', env('REDIS_PORT', 6379)),
            'password' => env('REDIS_CACHE_PASSWORD', env('REDIS_PASSWORD')),
            'database' => (int) env('REDIS_CACHE_DB', env('REDIS_CACHE_DATABASE', 1)),
            'timeout' => (float) env('REDIS_CACHE_TIMEOUT', env('REDIS_TIMEOUT', 1.0)),
            'read_timeout' => (float) env('REDIS_CACHE_READ_TIMEOUT', env('REDIS_READ_TIMEOUT', 1.0)),
            'persistent' => (bool) env('REDIS_CACHE_PERSISTENT', env('REDIS_PERSISTENT', false)),
            'prefix' => env('REDIS_CACHE_PREFIX', env('CACHE_PREFIX', 'pinoox:cache:')),
        ],
    ],
];
