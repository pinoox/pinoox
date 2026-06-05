<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),
    'cloud' => env('FILESYSTEM_CLOUD', 's3'),
    'app_disk' => env('FILESYSTEM_APP_DISK', env('FILESYSTEM_DISK', 'local')),
    'app_root' => env('FILESYSTEM_APPS_ROOT', '~storage/apps'),
    'app_prefix' => env('FILESYSTEM_APPS_PREFIX', 'apps'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => env('FILESYSTEM_LOCAL_ROOT', '~storage/app'),
            'throw' => env('FILESYSTEM_THROW', false),
        ],

        'public' => [
            'driver' => 'local',
            'root' => env('FILESYSTEM_PUBLIC_ROOT', '~storage/app/public'),
            'url' => env('FILESYSTEM_PUBLIC_URL', rtrim((string) env('APP_URL', ''), '/') . '/storage'),
            'visibility' => 'public',
            'throw' => env('FILESYSTEM_THROW', false),
        ],

        'apps' => [
            'driver' => 'local',
            'root' => env('FILESYSTEM_APPS_ROOT', '~storage/apps'),
            'visibility' => 'private',
            'throw' => env('FILESYSTEM_THROW', false),
        ],

        'temp' => [
            'driver' => 'local',
            'root' => env('FILESYSTEM_TEMP_ROOT', '~storage/tmp'),
            'visibility' => 'private',
            'throw' => env('FILESYSTEM_THROW', false),
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => env('FILESYSTEM_THROW', false),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        env('FILESYSTEM_PUBLIC_LINK', 'storage') => env('FILESYSTEM_PUBLIC_ROOT', '~storage/app/public'),
    ],

];
