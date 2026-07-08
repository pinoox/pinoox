<?php

return [
    'package' => 'com_pinoox_welcome',
    'name' => 'welcome',
    'title' => 'Welcome',
    'developer' => 'pinoox',
    'description' => 'Pinoox sample welcome app',
    'version-name' => '2.1.10',
    'version-code' => 16,
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

