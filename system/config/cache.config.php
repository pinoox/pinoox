<?php

return [
    'default' => env('CACHE_STORE', env('PINOOX_CACHE_STORE', 'file')),
    'prefix' => env('CACHE_PREFIX', env('PINOOX_CACHE_PREFIX', 'pinoox')),

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => env('CACHE_PATH', env('PINOOX_CACHE_PATH', '~storage/cache')),
            'ttl' => (int) env('CACHE_TTL', env('PINOOX_CACHE_TTL', 0)),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('CACHE_REDIS_CONNECTION', env('REDIS_CACHE_CONNECTION', 'cache')),
        ],
    ],
];
