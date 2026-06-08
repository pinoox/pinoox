<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Project paths
    |--------------------------------------------------------------------------
    |
    | Aliases: ~, ~config, ~pincore, ~pinker, ~storage.
    | Source config: pincore/config — baked runtime: pinker/config
    |
    */
    'config' => '~pincore/config',
    'pinker_config' => '~pinker/config',
    'system' => '~pincore/config',
    'apps' => env('PINOOX_APPS_PATH', 'apps'),
    'pinker' => env('PINOOX_PINKER_PATH', 'pinker'),
    'storage' => env('PINOOX_STORAGE_PATH', 'storage'),

    'project_config' => '~pincore/config',
    'project_registry' => '~pincore/config/apps.config.php',
    'project_router' => '~pincore/config/app-router.config.php',

    'platform_lang' => '~pincore/lang',
    'platform_migrations' => '~pincore/database/migrations',
    'platform_seed' => '~pincore/database/seed',
    'platform_patches' => '~pincore/patches',
    'platform_models' => '~pincore/Model',

    'stubs' => '~pincore/stubs',
    'app_file' => 'app.php',
    'app_migrations' => 'database/migrations',
    'app_seed' => 'database/seed',
    'app_patches' => 'patches',
    'app_lang' => 'lang',
    'app_config' => 'config',
    'wizard_tmp' => '~pinker/wizard_tmp',
];
