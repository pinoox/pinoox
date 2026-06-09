<?php

use App\com_pinoox_manager\Flow\BootFlow;
use App\com_pinoox_manager\Flow\ManagerAuthFlow;

return [
    'package' => 'com_pinoox_manager',
    'enable' => true,
    'sys-app' => true,
    'theme' => 'spark',
    'name' => 'manager',
    'description' => 'Manager',
    'icon' => 'icon.png',
    'version-name' => '2.2.0',
    'version-code' => 4,
    'developer' => 'Pinoox Team',
    'minpin' => 2,
    'lang' => 'fa',
    'transport' => [
        'user' => 'platform',
    ],
    'filesystem' => [
        'disk' => 'local',
        'default_access' => 'public',
        'thumb_width' => 512,
        'thumb_height' => 512,
    ],
    'auth' => [
        'mode' => 'jwt',
        'key' => 'manager_pinoox',
        'lifetime' => 30,
        'lifetime_unit' => 'day',
    ],
    'access' => [
        'groups' => [
            'admin' => ['*'],
            'manager' => ['manager.*'],
        ],
    ],
    'flow' => [
        BootFlow::class,
    ],
    'alias' => [
        'manager' => [
            'auth' => ManagerAuthFlow::class,
        ],
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
    'build' => [
        'exclude' => ['node_modules', 'tests'],
    ],
];

