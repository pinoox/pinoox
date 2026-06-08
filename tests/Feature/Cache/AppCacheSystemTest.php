<?php

use Pinoox\Component\Cache\AppCacheConfig;
use Pinoox\Component\Cache\AppCacheManager;
use Pinoox\Component\Cache\AppCachePath;
use Pinoox\Component\Cache\PhpCacheFile;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\AppCache;

it('registers app cache portal and helper', function () {
    expect(class_exists(AppCache::class))->toBeTrue()
        ->and(function_exists('app_cache_build'))->toBeTrue();
});

it('disables cache in development by default', function () {
    $config = AppCacheConfig::resolve('com_pinoox_welcome');

    expect($config)->toHaveKeys(['enabled', 'mode', 'stores', 'build_stores'])
        ->and($config['mode'])->toBe('development')
        ->and($config['enabled'])->toBeFalse()
        ->and($config['build_stores']['pinker'])->toBeTrue()
        ->and($config['build_stores']['routes'])->toBeFalse();
});

it('uses explicit build store overrides when configured', function () {
    writeAppCacheTestApp('com_test_cache_build', [
        'cache' => [
            'build' => [
                'stores' => [
                    'routes' => true,
                    'pinker' => false,
                ],
            ],
        ],
    ]);
    AppEngine::__rebuild();

    expect(AppCacheConfig::storesForBuild('com_test_cache_build'))
        ->toMatchArray(['routes' => true, 'pinker' => false, 'api' => false]);
});

it('resolves package cache path inside pinker apps folder', function () {
    $path = AppCachePath::store('com_acme_demo', 'routes');

    expect($path)->toContain('/pinker/apps/com_acme_demo/cache/routes.php');
});

it('writes and reads php cache files with legacy json migration', function () {
    $path = AppCachePath::root('com_test_php_cache') . '/sample.php';
    $legacy = PhpCacheFile::legacyPath($path);

    PhpCacheFile::write($path, ['actions' => [['name' => 'home']]]);

    expect(is_file($path))->toBeTrue()
        ->and(is_file($legacy))->toBeFalse()
        ->and(PhpCacheFile::read($path))->toBe(['actions' => [['name' => 'home']]]);

    @unlink($path);

    file_put_contents($legacy, json_encode(['actions' => [['name' => 'legacy']]], JSON_THROW_ON_ERROR));

    expect(PhpCacheFile::read($path))->toBe(['actions' => [['name' => 'legacy']]])
        ->and(is_file($path))->toBeTrue()
        ->and(is_file($legacy))->toBeFalse();

    PhpCacheFile::unlink($path);
    @rmdir(dirname($path));
});

it('lists all cache stores', function () {
    $stores = AppCacheManager::stores();

    expect($stores)->toHaveKeys(['routes', 'api', 'boot', 'twig', 'graphql', 'pinker']);
});

it('clears cache directory for a package', function () {
    $package = 'com_test_cache_demo';
    $root = AppCachePath::root($package);

    if (!is_dir($root)) {
        mkdir($root, 0777, true);
    }
    file_put_contents($root . '/manifest.php', "<?php\n\nreturn [];\n");

    AppCacheManager::clear($package);

    expect(is_dir($root))->toBeFalse();
});

function writeAppCacheTestApp(string $package, array $config): void
{
    $dir = testProjectRoot() . '/apps/' . $package;
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

