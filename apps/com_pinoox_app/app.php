<?php

return [
    'package' => 'com_pinoox_app',
    'name' => 'Pinoox App',
    'description' => 'Pinoox App starter — built with Pinoox',
    'developer' => 'Pinoox App Developer',
    'icon' => 'resource/icon.png',
    'version-name' => '1.2.4',
    'version-code' => 6,
    'enable' => true,
    'theme' => 'default',
    'lang' => 'en',
    'router' => [
        'routes' => [
            'routes/web.php',
            'routes/actions.php',
        ],
    ],
    'pinx' => [
        'type' => 'app',
        'minpin' => 3,
        'sign' => [
            'enabled' => false,
            'key' => null,
            'key_id' => null,
            'require' => false,
        ],
    ],
];
