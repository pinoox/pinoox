<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Project paths
    |--------------------------------------------------------------------------
    |
    | Relative to project root unless absolute. Aliases: ~, ~system, ~pincore,
    | ~pinker, ~storage.
    |
    */
    'system' => env('PINOOX_SYSTEM_PATH', 'system'),
    'apps' => env('PINOOX_APPS_PATH', 'apps'),
    'pinker' => env('PINOOX_PINKER_PATH', 'pinker'),
    'storage' => env('PINOOX_STORAGE_PATH', 'storage'),

    /*
    |--------------------------------------------------------------------------
    | System paths (~system/…)
    |--------------------------------------------------------------------------
    */
    'system_config' => env('PINOOX_SYSTEM_CONFIG_PATH', '~system/config'),
    'system_lang' => env('PINOOX_SYSTEM_LANG_PATH', '~system/lang'),
    'system_migrations' => env('PINOOX_SYSTEM_MIGRATIONS_PATH', '~system/database/migrations'),
    'system_seed' => env('PINOOX_SYSTEM_SEED_PATH', '~system/database/seed'),
    'system_patches' => env('PINOOX_SYSTEM_PATCHES_PATH', '~system/patches'),
    'system_models' => env('PINOOX_SYSTEM_MODELS_PATH', '~system/Model'),
    'system_registry' => env('PINOOX_SYSTEM_REGISTRY_PATH', '~system/config/apps.config.php'),
    'system_router' => env('PINOOX_SYSTEM_ROUTER_PATH', '~system/config/app/router.config.php'),

    /*
    |--------------------------------------------------------------------------
    | Framework & app conventions
    |--------------------------------------------------------------------------
    */
    'stubs' => env('PINOOX_STUBS_PATH', '~pincore/stubs'),
    'app_file' => env('PINOOX_APP_FILE', 'app.php'),
    'app_migrations' => env('PINOOX_APP_MIGRATIONS_PATH', 'database/migrations'),
    'app_seed' => env('PINOOX_APP_SEED_PATH', 'database/seed'),
    'app_patches' => env('PINOOX_APP_PATCHES_PATH', 'patches'),
    'app_lang' => env('PINOOX_APP_LANG_PATH', 'lang'),
    'app_config' => env('PINOOX_APP_CONFIG_PATH', 'config'),
    'wizard_tmp' => env('PINOOX_WIZARD_TMP_PATH', '~pinker/wizard_tmp'),
];
