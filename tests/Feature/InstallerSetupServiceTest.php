<?php

use App\com_pinoox_installer\Component\InstallerDatabase;
use Pinoox\Component\Database\DatabaseManager;

it('normalizes installer database input with pinx default prefix', function () {
    $config = InstallerDatabase::normalize([
        'host' => '127.0.0.1',
        'database' => 'pin',
        'username' => 'root',
        'password' => 'secret',
    ]);

    expect($config)
        ->toMatchArray([
            'host' => '127.0.0.1',
            'database' => 'pin',
            'username' => 'root',
            'password' => 'secret',
            'prefix' => DatabaseManager::DEFAULT_CORE_TABLE_PREFIX,
            'driver' => 'mysql',
            'port' => '3306',
        ]);
});

it('rejects database test without database name', function () {
    expect(InstallerDatabase::testConnection([
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => 'root',
    ]))->toBeFalse();
});
