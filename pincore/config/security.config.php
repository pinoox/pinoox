<?php

return [
    'key' => env('APP_KEY', ''),

    'hashing' => [
        'driver' => env('HASH_DRIVER', 'bcrypt'),

        'bcrypt' => [
            'rounds' => (int) env('BCRYPT_ROUNDS', 12),
            'verify' => env('HASH_VERIFY', true),
        ],

        'argon' => [
            'memory' => (int) env('ARGON_MEMORY', 65536),
            'threads' => (int) env('ARGON_THREADS', 1),
            'time' => (int) env('ARGON_TIME', 4),
            'verify' => env('HASH_VERIFY', true),
        ],
    ],
];
