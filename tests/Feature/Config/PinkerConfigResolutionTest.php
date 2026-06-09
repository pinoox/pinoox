<?php

use Pinoox\Component\Database\DatabaseConfig;
use Pinoox\Component\Package\AppManifest;
use Pinoox\Component\Package\Engine\AppEngine as PackageAppEngine;
use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Component\Test\AppTestKit;
use Pinoox\Support\SystemConfig;

/**
 * @group non-isolated
 * @group project-state
 *
 * Reads expectations from the local pinker/state overrides — not hard-coded values.
 */
it('loads core database config through pinker overrides', function () {
    AppTestKit::boot();

    $overridePath = SystemConfig::path('pinker') . '/state/config/database.config.php';

    if (!is_file($overridePath)) {
        test()->markTestSkipped('Database pinker override not present.');
    }

    $override = include $overridePath;
    $expectedDefault = (string) ($override['data']['default'] ?? '');
    $connectionKey = 'connections.' . $expectedDefault . '.database';
    $expectedDatabase = (string) ($override['data'][$connectionKey] ?? '');

    foreach (['DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_PORT'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    putenv('APP_ENV=' . RuntimeMode::PRODUCTION);
    $_ENV['APP_ENV'] = RuntimeMode::PRODUCTION;
    $_SERVER['APP_ENV'] = RuntimeMode::PRODUCTION;
    SystemConfig::clearCache();

    expect($expectedDefault)->not->toBe('')
        ->and(SystemConfig::get('database', 'default'))->toBe($expectedDefault)
        ->and(DatabaseConfig::connectionName())->toBe($expectedDefault);

    if ($expectedDatabase !== '') {
        expect(SystemConfig::get('database', $connectionKey))->toBe($expectedDatabase);
    }

    putenv('APP_ENV');
    unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
    SystemConfig::clearCache();
});

it('resolves app config from pinker with source.php defaults as fallback', function () {
    AppTestKit::boot();

    $overridePath = SystemConfig::path('pinker') . '/state/apps/com_pinoox_welcome/app.php';
    $sourcePath = SystemConfig::path('apps') . '/com_pinoox_welcome/app.php';

    if (!is_file($overridePath) || !is_file($sourcePath)) {
        test()->markTestSkipped('Welcome app pinker override or source not present.');
    }

    $source = include $sourcePath;
    $override = include $overridePath;
    $expectedOpen = (string) ($override['data']['open'] ?? '');

    $engine = new PackageAppEngine(
        SystemConfig::path('apps'),
        SystemConfig::rawPath('app_file', 'app.php'),
        SystemConfig::path('pinker'),
    );

    $config = $engine->config('com_pinoox_welcome');

    expect($config->get('package'))->toBe('com_pinoox_welcome')
        ->and($config->get('enable'))->toBeTrue();

    if ($expectedOpen !== '') {
        expect($config->get('open'))->toBe($expectedOpen);
    }

    $sourceMtime = filemtime($sourcePath);
    $overrideUpdatedAt = (int) ($override['info']['updated_at'] ?? 0);

    if (isset($source['lang']) && $sourceMtime > $overrideUpdatedAt) {
        expect($config->get('lang'))->toBe($source['lang']);
    }
});

it('resolves AppManifest through pinker with source.php defaults', function () {
    AppTestKit::boot();

    $overridePath = SystemConfig::path('pinker') . '/state/apps/com_pinoox_welcome/app.php';
    $sourcePath = SystemConfig::path('apps') . '/com_pinoox_welcome/app.php';

    if (!is_file($overridePath) || !is_file($sourcePath)) {
        test()->markTestSkipped('Welcome app pinker override or source not present.');
    }

    $source = include $sourcePath;
    $override = include $overridePath;
    $expectedOpen = (string) ($override['data']['open'] ?? '');

    $manifest = AppManifest::load('com_pinoox_welcome');

    expect($manifest['package'] ?? null)->toBe('com_pinoox_welcome')
        ->and($manifest['enable'] ?? null)->toBeTrue();

    if ($expectedOpen !== '') {
        expect($manifest['open'] ?? null)->toBe($expectedOpen);
    }

    $sourceMtime = filemtime($sourcePath);
    $overrideUpdatedAt = (int) ($override['info']['updated_at'] ?? 0);

    if (isset($source['lang']) && $sourceMtime > $overrideUpdatedAt) {
        expect($manifest['lang'] ?? null)->toBe($source['lang']);
    }
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
