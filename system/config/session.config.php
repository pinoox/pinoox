<?php

return [
    'driver' => env('SESSION_DRIVER', env('PINOOX_SESSION_DRIVER', 'file')),
    'lifetime' => (int) env('SESSION_LIFETIME', env('PINOOX_SESSION_LIFETIME', 120)),
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', env('PINOOX_SESSION_EXPIRE_ON_CLOSE', false)),
    'encrypt' => env('SESSION_ENCRYPT', env('PINOOX_SESSION_ENCRYPT', false)),
    'files' => env('SESSION_SAVE_PATH', env('PINOOX_SESSION_SAVE_PATH', '~storage/sessions')),
    'cookie' => env('SESSION_COOKIE', env('PINOOX_SESSION_COOKIE', 'pinoox_session')),
    'path' => env('SESSION_PATH', env('PINOOX_SESSION_PATH', '/')),
    'domain' => env('SESSION_DOMAIN', env('PINOOX_SESSION_DOMAIN')),
    'secure' => env('SESSION_SECURE_COOKIE', env('PINOOX_SESSION_SECURE_COOKIE', false)),
    'http_only' => env('SESSION_HTTP_ONLY', env('PINOOX_SESSION_HTTP_ONLY', true)),
    'same_site' => env('SESSION_SAME_SITE', env('PINOOX_SESSION_SAME_SITE', 'lax')),
];
