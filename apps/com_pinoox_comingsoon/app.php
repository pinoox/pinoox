<?php

return [
    'package' => 'com_pinoox_comingsoon',
    'name' => 'coming',
    'developer' => 'armin dev',
    'description' => 'Maintenance and coming-soon page for your site',
    'version-name' => '1.1.0',
    'version-code' => 2,
    'icon' => 'icon.png',
    'icon_style' => 'gradient',
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
