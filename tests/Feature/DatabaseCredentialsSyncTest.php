<?php

use App\com_pinoox_installer\Component\DatabaseCredentialsSync;
use Pinoox\Component\Runtime\RuntimeMode;

afterEach(function () {
    foreach (['PINOOX_MODE', 'APP_ENV', 'DB_CONNECTION'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
});

it('maps installer database config to env variables for development', function () {
    $env = DatabaseCredentialsSync::toEnvVariables([
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'pin',
        'username' => 'root',
        'password' => 'secret',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_bin',
        'prefix' => 'pinx_',
        'strict' => true,
        'engine' => null,
        'timezone' => '+03:30',
    ], RuntimeMode::DEVELOPMENT);

    expect($env)
        ->toMatchArray([
            'PINOOX_MODE' => 'development',
            'DB_CONNECTION' => 'development',
            'DB_DATABASE' => 'pin',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => 'secret',
        ]);
});

it('maps installer database config to production connection profile', function () {
    $env = DatabaseCredentialsSync::toEnvVariables([
        'driver' => 'mysql',
        'host' => 'db.hosting.local',
        'database' => 'site_db',
        'username' => 'site_user',
        'password' => 'secret',
    ], RuntimeMode::PRODUCTION);

    expect($env['PINOOX_MODE'])->toBe('production')
        ->and($env['DB_CONNECTION'])->toBe('production')
        ->and($env['DB_HOST'])->toBe('db.hosting.local');
});

it('does not expose env file writing from the installer', function () {
    expect(method_exists(DatabaseCredentialsSync::class, 'shouldWriteEnvFile'))->toBeFalse();
});
