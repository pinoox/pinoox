<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System App Registry
    |--------------------------------------------------------------------------
    |
    | Apps inside the project-level apps/ directory are discovered automatically.
    | Use this registry only for apps that live outside apps/ or need an
    | explicit project-level registration.
    |
    | Supported formats:
    |
    | 'packages' => [
    |     'com_vendor_app' => 'vendor/vendor-name/com_vendor_app',
    |     'com_external_app' => [
    |         'path' => '~/external/com_external_app',
    |         'enabled' => true,
    |     ],
    | ],
    |
    */
    'auto_discover' => env('PINOOX_APPS_AUTO_DISCOVER', true),
    'path' => env('PINOOX_APPS_PATH', 'apps'),
    'package_file' => env('PINOOX_APP_FILE', 'app.php'),

    'packages' => [
    ],
];
