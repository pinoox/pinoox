<?php

return [
    'package' => 'com_pinoox_comingsoon',
    'name' => 'coming',
    'title' => '@manifest.title',
    'developer' => 'armin dev',
    'description' => '@manifest.description',
    'version-name' => '1.1.0',
    'version-code' => 2,
    'icon' => 'icon.png',
    'enable' => true,
    'sys-app' => true,
    'theme' => 'default',
    'open' => 'app-view',
    'lang' => 'fa',
    'minpin' => 2,
    'transport' => [
        'user' => 'platform',
    ],
    'router' => [
        'routes' => [
            'routes/web.php',
        ],
    ],
    'pinx' => [
        'type' => 'app',
        'minpin' => 2,
    ],
];
