<?php

return [
    'key' => env('APP_KEY', env('PINOOX_APP_KEY', '')),
    'debug' => env('APP_DEBUG', env('PINOOX_DEBUG', false)),

    'hashing' => [
        'driver' => env('HASH_DRIVER', env('PINOOX_HASH_DRIVER', 'bcrypt')),

        'bcrypt' => [
            'rounds' => (int) env('BCRYPT_ROUNDS', env('PINOOX_BCRYPT_ROUNDS', 12)),
            'verify' => env('HASH_VERIFY', env('PINOOX_HASH_VERIFY', true)),
        ],

        'argon' => [
            'memory' => (int) env('ARGON_MEMORY', env('PINOOX_ARGON_MEMORY', 65536)),
            'threads' => (int) env('ARGON_THREADS', env('PINOOX_ARGON_THREADS', 1)),
            'time' => (int) env('ARGON_TIME', env('PINOOX_ARGON_TIME', 4)),
            'verify' => env('HASH_VERIFY', env('PINOOX_HASH_VERIFY', true)),
        ],
    ],
];
