<?php

return [
    'default' => env('REDIS_CONNECTION', 'default'),

    'prefix' => env('REDIS_PREFIX', 'pinoox:'),

    'connections' => [
        'default' => [
            'driver' => env('REDIS_CLIENT', 'phpredis'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => (int) env('REDIS_DB', 0),
            'timeout' => (float) env('REDIS_TIMEOUT', 1.0),
            'read_timeout' => (float) env('REDIS_READ_TIMEOUT', 1.0),
            'persistent' => (bool) env('REDIS_PERSISTENT', false),
            'prefix' => env('REDIS_PREFIX', 'pinoox:'),
        ],

        'cache' => [
            'driver' => env('REDIS_CACHE_CLIENT', env('REDIS_CLIENT', 'phpredis')),
            'host' => env('REDIS_CACHE_HOST', env('REDIS_HOST', '127.0.0.1')),
            'port' => (int) env('REDIS_CACHE_PORT', env('REDIS_PORT', 6379)),
            'password' => env('REDIS_CACHE_PASSWORD', env('REDIS_PASSWORD')),
            'database' => (int) env('REDIS_CACHE_DB', 1),
            'timeout' => (float) env('REDIS_CACHE_TIMEOUT', env('REDIS_TIMEOUT', 1.0)),
            'read_timeout' => (float) env('REDIS_CACHE_READ_TIMEOUT', env('REDIS_READ_TIMEOUT', 1.0)),
            'persistent' => (bool) env('REDIS_CACHE_PERSISTENT', env('REDIS_PERSISTENT', false)),
            'prefix' => env('REDIS_CACHE_PREFIX', env('CACHE_PREFIX', 'pinoox:cache:')),
        ],
    ],
];
