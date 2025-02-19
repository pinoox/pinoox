<?php
//pinoox app file, generated at "2019-12-28 14:38"

return array(
    'package'=>'com_pinoox_manager',
    'enable' => true,
    'theme' => 'spark',
    'name' => 'setting',
    'description' => 'Applications Management System (AMS)',
    'icon' => 'icon.png',
    'version-name' => '1.7.4',
    'version-code' => 6,
    'developer' => 'Pinoox Team',
    'minpin' => 1,
    'open' => 'setting-dashboard',
    'sys-app' => true,
    'flow' => [
        \App\com_pinoox_manager\Flow\BootFlow::class
    ]
);

//end of app