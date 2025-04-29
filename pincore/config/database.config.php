<?php

return [
    'default' => 'test',
    
    'test' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ],
    
    'development' =>
        [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'pinoox',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_bin',
            'prefix' => '',
            'strict' => true,
            'engine' => NULL,
            'timezone' => '+03:30',
        ],
];

