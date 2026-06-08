<?php

return [
    'default' => env('CACHE_STORE', 'file'),
    'prefix' => env('CACHE_PREFIX', 'pinoox'),

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => env('CACHE_PATH', '~storage/cache'),
            'ttl' => (int) env('CACHE_TTL', 0),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('CACHE_REDIS_CONNECTION', 'cache'),
        ],
    ],
];
