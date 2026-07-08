<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform distribution build (php pinoox build platform)
    |--------------------------------------------------------------------------
    |
    | Produces a .zip archive (not .pinx) for deploying the full Pinoox platform.
    | App packages keep using app.php → build settings and .pinx output.
    |
    */

    // Respect project and nested .gitignore files when collecting files
    'gitignore' => true,

    // Extra paths excluded from the archive (gitignore-style patterns; ! rules go in include)
    'exclude' => [
     '/docker',
     'node_modules',
     'tests',
     'theme/*/public',
     '/packages',
     'composer.lock',
     'composer.json',
     'package.json',
     'package-lock.json',
     'package-lock.json',
     '.env',
     '.env.example',
     '.gitignore',
     '.git',
     '.github',
     '.vscode',
     '.editorconfig',
     '.styleci.yml',
     '.phpunit.xml',
     '.phpunit.xml.dist',
     'docker-compose.yml',
     '.phpunit.result.cache',
     '.dockerignore',
     '/CHANGELOG.md',
     '/CONTRIBUTING.md',
     '/LICENSE',
     '/README.md',
     'vite.config.js',
     'vite.config.ts',
     'vite.config.tsx',
     'vite.config.tsx',
     '.gitattributes',
    ],

    // Force-include paths using gitignore-style patterns (supports negation overrides)
    'include' => [],

    // Bundle vendor/ from an existing `composer install --no-dev` (build does not run Composer)
    'composer' => true,

    // Bundle apps/{package}/vendor when the app has its own composer.json requires
    'app_composer' => true,

    // Drop theme frontend source trees (theme/*/src); dist/ and Twig stay
    'exclude_theme_src' => true,

    // Omit require-dev and autoload-dev from composer.json files in the archive
    'strip_require_dev' => true,

    // Drop tests/, docs/, .github/ and similar paths from bundled vendor trees
    'vendor_prune' => false,

    // Output directory for .zip files (default: ~/pinx/export/platform)
    // 'output_dir' => '~/pinx/export/platform',

    // Write BUILD.json metadata into the archive root
    'manifest' => true,
];
