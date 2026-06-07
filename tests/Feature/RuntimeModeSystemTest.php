<?php

use Pinoox\Component\Cache\AppCacheConfig;
use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Component\Test\AppTestKit;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Config;
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\Mode;

beforeEach(function () {
    AppTestKit::boot();
    foreach (['com_test_runtime_mode', 'com_test_runtime_auth', 'com_test_runtime_cache', 'com_test_runtime_cache_on', 'com_test_runtime_profile'] as $package) {
        deleteRuntimeModeTestApp($package);
    }
    AppEngine::__rebuild();
});

afterEach(function () {
    foreach (['com_test_runtime_mode', 'com_test_runtime_auth', 'com_test_runtime_cache', 'com_test_runtime_cache_on', 'com_test_runtime_profile'] as $package) {
        deleteRuntimeModeTestApp($package);
    }
    Config::name('~pinoox')->set('mode', 'test');
    AppEngine::__rebuild();
});

it('normalizes mode aliases', function () {
    expect(RuntimeMode::normalize('dev'))->toBe('development')
        ->and(RuntimeMode::normalize('prod'))->toBe('production')
        ->and(RuntimeMode::normalize('testing'))->toBe('test');
});

it('reads live global mode from config after test boot', function () {
    expect(Mode::name())->toBe('test')
        ->and(runtime_mode())->toBe('test')
        ->and(Mode::isTest())->toBeTrue();
});

it('resolves app runtime overrides independently from auth mode', function () {
    writeRuntimeModeTestApp('com_test_runtime_auth', [
        'runtime' => [
            'mode' => 'production',
            'debug' => false,
        ],
        'auth' => [
            'mode' => 'session',
        ],
    ]);
    AppEngine::__rebuild();

    expect(Mode::name('com_test_runtime_auth'))->toBe('production')
        ->and(Mode::debug('com_test_runtime_auth'))->toBeFalse()
        ->and(Mode::isProduction('com_test_runtime_auth'))->toBeTrue()
        ->and(Mode::name())->toBe('test');
});

it('maps database connection profile by runtime mode', function () {
    expect(Mode::databaseConnection())->toBe('test')
        ->and(Mode::databaseConnection('com_test_runtime_mode'))->toBe('test');

    Config::name('~pinoox')->set('mode', 'production');
    AppEngine::__rebuild();

    expect(Mode::databaseConnection())->toBe('production')
        ->and(DB::mode())->toBe('production');
});

it('derives cache defaults from runtime mode', function () {
    writeRuntimeModeTestApp('com_test_runtime_cache', []);
    AppEngine::__rebuild();

    Config::name('~pinoox')->set('mode', 'development');

    expect(Mode::cacheEnabledByDefault('com_test_runtime_cache'))->toBeFalse()
        ->and(Mode::cacheMode('com_test_runtime_cache'))->toBe('development')
        ->and(AppCacheConfig::resolve('com_test_runtime_cache')['enabled'])->toBeFalse()
        ->and(AppCacheConfig::storesForBuild('com_test_runtime_cache'))
        ->toMatchArray(['pinker' => true, 'routes' => false]);

    Config::name('~pinoox')->set('mode', 'production');

    expect(Mode::cacheEnabledByDefault('com_test_runtime_cache'))->toBeFalse()
        ->and(Mode::cacheMode('com_test_runtime_cache'))->toBe('production')
        ->and(AppCacheConfig::resolve('com_test_runtime_cache')['enabled'])->toBeFalse()
        ->and(AppCacheConfig::storesForBuild('com_test_runtime_cache')['routes'])->toBeTrue();
});

it('enables runtime cache only when app config opts in', function () {
    writeRuntimeModeTestApp('com_test_runtime_cache_on', [
        'cache' => ['enabled' => true],
    ]);
    AppEngine::__rebuild();

    Config::name('~pinoox')->set('mode', 'production');

    expect(AppCacheConfig::enabled('com_test_runtime_cache_on'))->toBeTrue()
        ->and(AppCacheConfig::storeEnabled('routes', 'com_test_runtime_cache_on'))->toBeTrue();
});

it('builds a runtime profile snapshot', function () {
    Config::name('~pinoox')->set('mode', 'test');

    writeRuntimeModeTestApp('com_test_runtime_profile', [
        'runtime' => ['mode' => 'staging', 'debug' => true],
    ]);
    AppEngine::__rebuild();

    $profile = Mode::profile('com_test_runtime_profile');

    expect($profile)->toMatchArray([
        'mode' => 'staging',
        'debug' => true,
        'production' => false,
        'database' => 'staging',
        'cache_mode' => 'production',
        'cache_enabled' => false,
        'package' => 'com_test_runtime_profile',
    ]);
});

function writeRuntimeModeTestApp(string $package, array $config): void
{
    $dir = dirname(__DIR__, 2) . '/apps/' . $package;

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($dir . '/app.php', "<?php\n\nreturn " . var_export([
        'package' => $package,
        'enable' => true,
        'name' => $package,
        ...$config,
    ], true) . ";\n");
}

function deleteRuntimeModeTestApp(string $package): void
{
    $root = dirname(__DIR__, 2);
    deleteRuntimeModeDirectory($root . '/apps/' . $package);
    deleteRuntimeModeDirectory($root . '/pinker/apps/' . $package);
}

function deleteRuntimeModeDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
}

