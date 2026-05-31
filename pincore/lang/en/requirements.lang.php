<?php

return [
    'meta' => [
        'dir' => 'ltr',
        'lang' => 'en',
    ],
    'labels' => [
        'required' => 'Required version (composer.json)',
        'current' => 'Current server PHP version',
        'hint_title' => 'How to fix',
    ],
    'php' => [
        'title' => 'Invalid PHP version',
        'badge' => 'System requirement',
        'heading' => 'Pinoox cannot run',
        'message' => 'The PHP version on this server does not satisfy the requirement defined in composer.json.',
        'hints' => [
            'In your hosting panel (cPanel, DirectAdmin, etc.), switch PHP to {required} or higher.',
            'In MAMP/XAMPP/WAMP, change the active PHP version in server settings.',
            'After upgrading, run php -v and reload this page.',
            'This page replaces the Composer platform error with an installer-friendly message.',
        ],
    ],
    'vendor' => [
        'title' => 'Composer dependencies are missing',
        'heading' => 'vendor folder was not found',
        'message' => 'Project dependencies must be installed with Composer before using the installer.',
        'hints' => [
            'Run composer install in the project root.',
            'Make sure PHP CLI is {required} or higher.',
            'If composer install fails on PHP version, upgrade PHP first.',
        ],
    ],
];
