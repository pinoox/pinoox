<?php

use App\com_pinoox_installer\Component\DatabaseCredentialsSync;
use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Component\Store\Baker\EnvSensitiveConfig;
use Pinoox\Component\Test\AppTestKit;
use Pinoox\Portal\Config;
use Pinoox\Portal\Pinker;
use Pinoox\Support\SystemConfig;

it('documents env priority metadata on env-sensitive pinker overrides', function () {
    AppTestKit::boot();

    $overridePath = SystemConfig::path('pinker') . '/state/config/database.config.php';
    $overrideBackup = is_file($overridePath) ? file_get_contents($overridePath) : null;

    try {
        Config::name('~pinoox')->set('mode', 'production');
        SystemConfig::clearCache();

        expect(DatabaseCredentialsSync::persist([
            'driver' => 'mysql',
            'host' => 'pinker-host',
            'port' => '3306',
            'database' => 'pin',
            'username' => 'root',
            'password' => 'secret',
            'prefix' => 'pinx_',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_bin',
            'strict' => true,
            'engine' => null,
            'timezone' => '+03:30',
        ], 'mysql'))->toBeTrue();

        $override = include $overridePath;

        expect($override['info']['env_sensitive'] ?? null)->toBe('yes')
            ->and($override['info']['env_priority'] ?? null)->toBe('env-over-pinker')
            ->and($override['info']['env_resolution'] ?? null)->toContain('defined-env-key-overrides-pinker');
    } finally {
        if ($overrideBackup !== null) {
            file_put_contents($overridePath, $overrideBackup);
        } elseif (is_file($overridePath)) {
            unlink($overridePath);
        }
    }
});

it('uses env values instead of pinker when the env key is defined', function () {
    AppTestKit::boot();

    $mainFile = SystemConfig::configPath('database.config.php');
    $bakedFile = SystemConfig::pinkerConfigPath('database.config.php');
    $overridePath = SystemConfig::path('pinker') . '/state/config/database.config.php';
    $overrideBackup = is_file($overridePath) ? file_get_contents($overridePath) : null;

    putenv('APP_ENV=' . RuntimeMode::DEVELOPMENT);
    $_ENV['APP_ENV'] = RuntimeMode::DEVELOPMENT;
    $_SERVER['APP_ENV'] = RuntimeMode::DEVELOPMENT;
    putenv('DB_HOST=env-host');
    $_ENV['DB_HOST'] = 'env-host';
    $_SERVER['DB_HOST'] = 'env-host';

    try {
        Config::name('~pinoox')->set('mode', RuntimeMode::DEVELOPMENT);
        SystemConfig::clearCache();

        expect(DatabaseCredentialsSync::persist([
            'driver' => 'mysql',
            'host' => 'pinker-host',
            'port' => '3306',
            'database' => 'pin',
            'username' => 'root',
            'password' => 'secret',
            'prefix' => 'pinx_',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_bin',
            'strict' => true,
            'engine' => null,
            'timezone' => '+03:30',
        ], 'mysql'))->toBeTrue();

        SystemConfig::clearCache();

        $picked = Pinker::create($mainFile, $bakedFile)->pickup();
        $connections = $picked['connections'] ?? [];

        expect($connections['mysql']['host'] ?? null)->toBe('env-host');
    } finally {
        putenv('DB_HOST');
        unset($_ENV['DB_HOST'], $_SERVER['DB_HOST']);
        putenv('APP_ENV');
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);

        if ($overrideBackup !== null) {
            file_put_contents($overridePath, $overrideBackup);
        } elseif (is_file($overridePath)) {
            unlink($overridePath);
        }

        SystemConfig::clearCache();
    }
});

it('falls back to pinker when the mapped env key is not defined', function () {
    AppTestKit::boot();

    $mainFile = SystemConfig::configPath('database.config.php');
    $bakedFile = SystemConfig::pinkerConfigPath('database.config.php');
    $overridePath = SystemConfig::path('pinker') . '/state/config/database.config.php';
    $overrideBackup = is_file($overridePath) ? file_get_contents($overridePath) : null;

    putenv('APP_ENV=' . RuntimeMode::PRODUCTION);
    $_ENV['APP_ENV'] = RuntimeMode::PRODUCTION;
    $_SERVER['APP_ENV'] = RuntimeMode::PRODUCTION;

    foreach (['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CONNECTION'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    try {
        Config::name('~pinoox')->set('mode', RuntimeMode::PRODUCTION);
        SystemConfig::clearCache();

        expect(DatabaseCredentialsSync::persist([
            'driver' => 'mysql',
            'host' => 'pinker-host',
            'port' => '3306',
            'database' => 'pin',
            'username' => 'root',
            'password' => 'secret',
            'prefix' => 'pinx_',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_bin',
            'strict' => true,
            'engine' => null,
            'timezone' => '+03:30',
        ], 'mysql'))->toBeTrue();

        SystemConfig::clearCache();

        $picked = Pinker::create($mainFile, $bakedFile)->pickup();

        expect($picked['connections']['mysql']['host'] ?? null)->toBe('pinker-host');
    } finally {
        putenv('APP_ENV');
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);

        if ($overrideBackup !== null) {
            file_put_contents($overridePath, $overrideBackup);
        } elseif (is_file($overridePath)) {
            unlink($overridePath);
        }

        SystemConfig::clearCache();
    }
});

it('maps database pinker paths to env keys', function () {
    $mainFile = SystemConfig::configPath('database.config.php');

    expect(EnvSensitiveConfig::envKeyForConfigPath($mainFile, 'default'))->toBe('DB_CONNECTION')
        ->and(EnvSensitiveConfig::envKeyForConfigPath($mainFile, 'connections.mysql.host'))->toBe('DB_HOST')
        ->and(EnvSensitiveConfig::envKeyForConfigPath($mainFile, 'connections.mariadb.database'))->toBe('DB_DATABASE');
});
