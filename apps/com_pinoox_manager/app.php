<?php

use App\com_pinoox_manager\Flow\ManagerAuthFlow;

return array(
    'package' => 'com_pinoox_manager',
    'enable' => true,
    'theme' => 'spark',
    'name' => 'manager',
    'description' => 'Manager',
    'icon' => 'icon.png',
    'version-name' => '2.0.0',
    'version-code' => 2,
    'developer' => 'Pinoox Team',
    'minpin' => 2,
    'sys-app' => true,
    'lang' => 'fa',
    'alias' => [
        'manager' => [
            'auth' => ManagerAuthFlow::class,
        ],
    ],
);

//end of app