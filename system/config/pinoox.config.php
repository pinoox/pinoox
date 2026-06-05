<?php

return [
    'name' => env('APP_NAME', env('PINOOX_APP_NAME', 'Pinoox')),
    'version_code' => (int) env('PINOOX_VERSION_CODE', 19),
    'version_name' => env('PINOOX_VERSION_NAME', '2.1.0'),
    'apps_location' => env('PINOOX_APPS_PATH', 'apps'),
    'lang' => env('APP_LOCALE', env('PINOOX_LANG', 'en')),
    'lang_fallback' => env('APP_FALLBACK_LOCALE', env('PINOOX_LANG_FALLBACK', 'en')),
    'faker_locale' => env('APP_FAKER_LOCALE', env('PINOOX_FAKER_LOCALE', 'en_US')),
    'mode' => env('APP_ENV', env('PINOOX_MODE', 'development')), // development | production | test
    'debug' => env('APP_DEBUG', env('PINOOX_DEBUG', false)),
    'log' => [
        'path' => env('PINOOX_LOG_PATH', sys_get_temp_dir() . '/pinoox.log'),
        'channel' => env('LOG_CHANNEL', env('PINOOX_LOG_CHANNEL', 'app')),
        'level' => env('LOG_LEVEL', env('PINOOX_LOG_LEVEL', 'debug')),
    ],
];
