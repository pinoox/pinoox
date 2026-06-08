<?php

use Pinoox\Component\Database\DatabaseConfig;
use Pinoox\Component\Package\AppManifest;
use Pinoox\Component\Package\Engine\AppEngine as PackageAppEngine;
use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Component\Test\AppTestKit;
use Pinoox\Support\SystemConfig;

it('loads core database config through pinker overrides', function () {
    AppTestKit::boot();

    $overridePath = SystemConfig::path('pinker') . '/state/config/database.config.php';

    if (!is_file($overridePath)) {
        test()->markTestSkipped('Database pinker override not present.');
    }

    foreach (['DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_PORT'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    putenv('APP_ENV=' . RuntimeMode::PRODUCTION);
    $_ENV['APP_ENV'] = RuntimeMode::PRODUCTION;
    $_SERVER['APP_ENV'] = RuntimeMode::PRODUCTION;
    SystemConfig::clearCache();

    $default = SystemConfig::get('database', 'default');

    expect($default)->toBe('mariadb')
        ->and(DatabaseConfig::connectionName())->toBe('mariadb')
        ->and(SystemConfig::get('database', 'connections.mariadb.database'))->toBe('pinm');

    putenv('APP_ENV');
    unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
    SystemConfig::clearCache();
});

it('resolves app config from pinker with source.php defaults as fallback', function () {
    AppTestKit::boot();

    $overridePath = SystemConfig::path('pinker') . '/state/apps/com_pinoox_welcome/app.php';

    if (!is_file($overridePath)) {
        test()->markTestSkipped('Welcome app pinker override not present.');
    }

    $engine = new PackageAppEngine(
        SystemConfig::path('apps'),
        SystemConfig::rawPath('app_file', 'app.php'),
        SystemConfig::path('pinker'),
    );

    $config = $engine->config('com_pinoox_welcome');

    expect($config->get('lang'))->toBe('fa')
        ->and($config->get('enable'))->toBeTrue()
        ->and($config->get('package'))->toBe('com_pinoox_welcome')
        ->and($config->get('dock'))->toBeTrue();
});

it('resolves AppManifest through pinker with source.php defaults', function () {
    AppTestKit::boot();

    $overridePath = SystemConfig::path('pinker') . '/state/apps/com_pinoox_welcome/app.php';

    if (!is_file($overridePath)) {
        test()->markTestSkipped('Welcome app pinker override not present.');
    }

    $manifest = AppManifest::load('com_pinoox_welcome');

    expect($manifest['lang'] ?? null)->toBe('fa')
        ->and($manifest['enable'] ?? null)->toBeTrue()
        ->and($manifest['package'] ?? null)->toBe('com_pinoox_welcome')
        ->and($manifest['dock'] ?? null)->toBeTrue();
});

it('prefers defined DB_CONNECTION env over pinker default connection', function () {
    AppTestKit::boot();

    putenv('DB_CONNECTION=mysql');
    $_ENV['DB_CONNECTION'] = 'mysql';
    $_SERVER['DB_CONNECTION'] = 'mysql';
    SystemConfig::clearCache();

    expect(DatabaseConfig::requestedConnectionName())->toBe('mysql');

    putenv('DB_CONNECTION');
    unset($_ENV['DB_CONNECTION'], $_SERVER['DB_CONNECTION']);
    SystemConfig::clearCache();
});
