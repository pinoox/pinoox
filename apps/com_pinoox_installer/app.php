<?php
//pinoox app file, generated at "2020-05-19 00:41"

return array(
    'enable' => true,
    'icon' => 'icon.png',
    'name' => 'installer',
    'theme' => 'magic',
    'version-name' => '2.0',
    'version-code' => 7,
    'lang' => 'en',
    'user' => 'com_pinoox_manager',
    'flow' => [
        \App\com_pinoox_installer\Flow\BootFlow::class
    ],
);

//end of app