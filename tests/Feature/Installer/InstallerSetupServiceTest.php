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
        ])
        ->and(InstallerDatabase::connectionName(['connection' => 'mysql']))->toBe('mysql');
});

it('normalizes mariadb installer input to mysql driver for illuminate 10', function () {
    $config = InstallerDatabase::normalize([
        'connection' => 'mariadb',
        'host' => '127.0.0.1',
        'database' => 'pin',
        'username' => 'root',
        'password' => 'secret',
    ]);

    expect($config['driver'])->toBe('mysql')
        ->and($config['port'])->toBe('3306')
        ->and(InstallerDatabase::connectionName(['connection' => 'mariadb']))->toBe('mariadb');
});

it('uses pgsql defaults when connection is pgsql', function () {
    $config = InstallerDatabase::normalize([
        'connection' => 'pgsql',
        'host' => '127.0.0.1',
        'database' => 'pin',
        'username' => 'postgres',
        'password' => 'secret',
    ]);

    expect($config['driver'])->toBe('pgsql')
        ->and($config['port'])->toBe('5432');
});

it('rejects database test without database name', function () {
    expect(InstallerDatabase::testConnection([
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => 'root',
    ]))->toBeFalse();
});

