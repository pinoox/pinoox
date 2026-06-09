<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Canonical default domain (optional)
    |--------------------------------------------------------------------------
    |
    | Used by docs, emails, and CLI when no HTTP request exists.
    | Does NOT restrict which hosts may access the site.
    |
    */
    'default' => env('PINOOX_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Dedicated host → app mappings
    |--------------------------------------------------------------------------
    |
    | Any host NOT listed here uses default domain routing (app-router.config.php).
    |
    | 'shop.example.com' => 'com_my_shop',
    |
    | '*.example.com' => [
    |     'package' => 'com_tenant',
    |     'subdomain' => '{sub}',
    | ],
    */
    'hosts' => [
    ],
];
