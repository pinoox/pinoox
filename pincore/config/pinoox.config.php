<?php

return [
    'name' => env('APP_NAME', 'Pinoox'),
    'version_code' => 30,
    'version_name' => '3.0.0',
    'lang' => env('APP_LOCALE', 'en'),
    'lang_fallback' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Runtime mode
    |--------------------------------------------------------------------------
    |
    | development | production | staging | test
    |
    | Apps may override via app.php → runtime.mode / runtime.debug
    |
    */
    'mode' => runtime_env_mode(),
    /*
    |--------------------------------------------------------------------------
    | Debug (APP_DEBUG)
    |--------------------------------------------------------------------------
    |
    | Application debug: route action validation, Twig debug, etc.
    | Default: false in production, true in other modes (see EnvBootstrap).
    |
    */
    'debug' => env('APP_DEBUG', \Pinoox\Component\Runtime\RuntimeMode::defaultDebugForMode()),

    /*
    |--------------------------------------------------------------------------
    | Pinoox Exception (PINOOX_EXCEPTION)
    |--------------------------------------------------------------------------
    |
    | Rich exception page and boot-time error handler (PinooxDebug).
    | Independent of APP_DEBUG — defaults to true in all modes.
    |
    */
    'exception' => env('PINOOX_EXCEPTION', true),

    'log' => [
        'path' => env('LOG_PATH', '~storage/logs/pinoox.log'),
        'channel' => env('LOG_CHANNEL', 'pinoox'),
        'level' => env('LOG_LEVEL', 'debug'),
        'rotate' => true,
        'max_files' => 14,
    ],
];
