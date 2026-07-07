<?php

use App\com_pinoox_installer\Flow\BootFlow;

return [
    'package' => 'com_pinoox_installer',
    'enable' => true,
    'sys-app' => true,
    'icon' => 'icon.png',
    'name' => 'installer',
    'title' => '@manifest.title',
    'description' => '@manifest.description',
    'developer' => 'Pinoox Team',
    'theme' => 'magic',
    'version-name' => '2.1.5',
    'version-code' => 13,
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

