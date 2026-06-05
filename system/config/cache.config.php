<?php

return [
    'default' => env('CACHE_STORE', env('PINOOX_CACHE_STORE', 'file')),
    'prefix' => env('CACHE_PREFIX', env('PINOOX_CACHE_PREFIX', 'pinoox')),

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => env('CACHE_PATH', env('PINOOX_CACHE_PATH', '~storage/cache')),
            'namespace' => env('CACHE_PREFIX', env('PINOOX_CACHE_PREFIX', 'pinoox')),
            'ttl' => (int) env('CACHE_TTL', env('PINOOX_CACHE_TTL', 0)),
        ],
    ],
];
