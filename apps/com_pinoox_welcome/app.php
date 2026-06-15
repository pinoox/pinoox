<?php

return [
    'package' => 'com_pinoox_welcome',
    'name' => 'welcome',
    'title' => '@manifest.title',
    'developer' => 'pinoox',
    'description' => '@manifest.description',
    'version-name' => '2.1.0',
    'version-code' => 6,
    'icon' => 'icon.png',
    'enable' => true,
    'sys-app' => true,
    'theme' => 'welcome',
    'frontend' => [
        'profile' => 'hybrid',
        'stack' => 'vue',
    ],
    'minpin' => 2,
    'router' => [
        'routes' => [
            'routes/web.php',
            'routes/actions.php',
        ],
    ],
    'pinx' => [
        'type' => 'app',
        'minpin' => 2,
    ],
];

