<?php

return [
    'package' => 'com_pinoox_comingsoon',
    'name' => 'coming',
    'developer' => 'armin dev',
    'description' => 'Maintenance and coming-soon page for your site',
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
        'user' => 'pincore',
        'token' => 'pincore',
    ],
    'auth' => [
        'mode' => 'session',
        'key' => 'comingsoon_pinoox',
        'lifetime' => 30,
        'lifetime_unit' => 'day',
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
