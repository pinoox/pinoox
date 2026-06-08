<?php

use Pinoox\Component\Database\DatabaseConfig;

it('defines all laravel-style sql database connections', function () {
    $config = require testProjectRoot() . '/pincore/config/database.config.php';

    expect($config['connections'])->toHaveKeys(['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'])
        ->and($config['migrations']['table'] ?? null)->toBe('history');
});

it('resolves each configured connection name', function () {
    $root = DatabaseConfig::normalize(require testProjectRoot() . '/pincore/config/database.config.php');

    foreach (['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'] as $name) {
        $connection = DatabaseConfig::connectionConfig($root, $name);

        expect($connection)->toBeArray()
            ->and($connection['driver'] ?? null)->not->toBeEmpty();
    }
});

it('maps mariadb driver to mysql for illuminate 10 compatibility', function () {
    $root = DatabaseConfig::normalize(require testProjectRoot() . '/pincore/config/database.config.php');
    $config = DatabaseConfig::connectionConfig($root, 'mariadb');

    expect($config['driver'])->toBe('mysql');
});

it('exposes supported connection names from config', function () {
    $names = DatabaseConfig::supportedConnections();

    expect($names)->toContain('mysql', 'mariadb', 'pgsql', 'sqlsrv', 'sqlite');
});

it('provides database_path helper for sqlite files', function () {
    expect(function_exists('database_path'))->toBeTrue()
        ->and(database_path('database.sqlite'))->toEndWith('storage/app/database/database.sqlite');
});
