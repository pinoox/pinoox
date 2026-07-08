<?php

use App\com_pinoox_installer\Flow\BootFlow;

return [
    'package' => 'com_pinoox_installer',
    'enable' => true,
    'sys-app' => true,
    'icon' => 'icon.png',
    'name' => 'installer',
    'title' => 'Installer',
    'description' => 'Pinoox web installer for first-time project setup, database validation, and environment checks.',
    'developer' => 'Pinoox Team',
    'theme' => 'magic',
    'version-name' => '2.1.9',
    'version-code' => 17,
    'lang' => 'en',
    'minpin' => 2,
    'transport' => [
        'user' => 'platform',
    ],
    'flow' => [
        BootFlow::class,
    ],
    'router' => [
        'routes' => [
            'routes/actions.php',
            'routes/api.php',
            'routes/web.php',
        ],
    ],
    'pinx' => [
        'type' => 'app',
        'minpin' => 2,
    ],
    'build' => [
        'exclude' => ['node_modules'],
    ],
];

