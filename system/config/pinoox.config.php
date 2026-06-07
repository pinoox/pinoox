<?php

return [
    'name' => env('APP_NAME', env('PINOOX_APP_NAME', 'Pinoox')),
    'version_code' => (int) env('PINOOX_VERSION_CODE', 20),
    'version_name' => env('PINOOX_VERSION_NAME', '2.2.0'),
    'lang' => env('APP_LOCALE', env('PINOOX_LANG', 'en')),
    'lang_fallback' => env('APP_FALLBACK_LOCALE', env('PINOOX_LANG_FALLBACK', 'en')),
    'faker_locale' => env('APP_FAKER_LOCALE', env('PINOOX_FAKER_LOCALE', 'en_US')),

    /*
    |--------------------------------------------------------------------------
    | Runtime mode
    |--------------------------------------------------------------------------
    |
    | development | production | staging | test
    |
    | - development: debug tools, cache off by default, verbose logs
    | - production:  optimized cache, strict errors, warning logs
    | - staging:     production-like with optional debug via APP_DEBUG
    | - test:        used by php pinoox test / Pest (sqlite :memory:)
    |
    | Apps may override via app.php → runtime.mode / runtime.debug
    |
    */
    'mode' => env('APP_ENV', env('PINOOX_MODE', 'development')),
    'debug' => env('APP_DEBUG', env('PINOOX_DEBUG', false)),

    'log' => [
        'path' => env('PINOOX_LOG_PATH', '~storage/logs/pinoox.log'),
        'channel' => env('LOG_CHANNEL', env('PINOOX_LOG_CHANNEL', 'pinoox')),
        'level' => env('LOG_LEVEL', env('PINOOX_LOG_LEVEL')),
        'rotate' => env('PINOOX_LOG_ROTATE', true),
        'max_files' => (int) env('PINOOX_LOG_MAX_FILES', 14),
    ],
];
