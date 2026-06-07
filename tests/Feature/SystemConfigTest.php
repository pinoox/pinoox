<?php

use Pinoox\Component\Package\Engine\AppEngine as PackageAppEngine;
use Pinoox\Support\SystemConfig;

beforeEach(function () {
    SystemConfig::clearCache();
    restoreSystemConfigTestEnv();
    deleteSystemConfigTestDirectory(systemConfigTestRoot());
});

afterEach(function () {
    SystemConfig::clearCache();
    restoreSystemConfigTestEnv();
    deleteSystemConfigTestDirectory(systemConfigTestRoot());
});

it('resolves configurable project paths from env values', function () {
    setSystemConfigTestEnv('PINOOX_PINKER_PATH', 'tests/Fixtures/system_config/custom_pinker');
    setSystemConfigTestEnv('PINOOX_STORAGE_PATH', 'tests/Fixtures/system_config/custom_storage');
    setSystemConfigTestEnv('PINOOX_WIZARD_TMP_PATH', '~pinker/tmp/wizard');
    SystemConfig::clearCache();

    $basePath = str_replace('\\', '/', dirname(__DIR__, 2));

    expect(SystemConfig::path('pinker'))->toBe($basePath . '/tests/Fixtures/system_config/custom_pinker')
        ->and(SystemConfig::path('storage'))->toBe($basePath . '/tests/Fixtures/system_config/custom_storage')
        ->and(SystemConfig::path('wizard_tmp'))->toBe($basePath . '/tests/Fixtures/system_config/custom_pinker/tmp/wizard');
});

it('uses top-level patch paths outside the database folders', function () {
    $basePath = str_replace('\\', '/', dirname(__DIR__, 2));

    expect(SystemConfig::path('system_patches'))->toBe($basePath . '/system/patches')
        ->and(SystemConfig::rawPath('app_patches'))->toBe('patches');
});

it('lets the app engine discover apps from a custom env path', function () {
    $appsPath = systemConfigTestRoot() . '/custom_apps';
    $appPath = $appsPath . '/com_test_custom';

    mkdir($appPath, 0755, true);
    file_put_contents($appPath . '/app.php', "<?php\n\nreturn ['package' => 'com_test_custom', 'enable' => true, 'name' => 'custom'];\n");

    setSystemConfigTestEnv('PINOOX_APPS_PATH', 'tests/Fixtures/system_config/custom_apps');
    setSystemConfigTestEnv('PINOOX_PINKER_PATH', 'tests/Fixtures/system_config/custom_pinker');
    SystemConfig::clearCache();

    $engine = new PackageAppEngine(
        SystemConfig::path('apps'),
        SystemConfig::rawPath('app_file', 'app.php'),
        SystemConfig::path('pinker'),
    );

    expect($engine->exists('com_test_custom'))->toBeTrue()
        ->and($engine->path('com_test_custom'))->toBe($appPath);
});

function systemConfigTestRoot(): string
{
    return str_replace('\\', '/', dirname(__DIR__, 2) . '/tests/Fixtures/system_config');
}

function setSystemConfigTestEnv(string $key, string $value): void
{
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

function restoreSystemConfigTestEnv(): void
{
    foreach ([
        'APP_NAME',
        'APP_ENV',
        'APP_DEBUG',
        'APP_LOCALE',
        'APP_FALLBACK_LOCALE',
        'LOG_CHANNEL',
        'LOG_LEVEL',
        'CACHE_STORE',
        'CACHE_PREFIX',
        'CACHE_PATH',
        'SESSION_SAVE_PATH',
        'BCRYPT_ROUNDS',
        'FILESYSTEM_APPS_ROOT',
        'PINOOX_APPS_PATH',
        'PINOOX_PINKER_PATH',
        'PINOOX_STORAGE_PATH',
        'PINOOX_WIZARD_TMP_PATH',
    ] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
}

function deleteSystemConfigTestDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
}

it('supports Laravel-compatible env aliases for core runtime config', function () {
    setSystemConfigTestEnv('APP_NAME', 'PinLab');
    setSystemConfigTestEnv('APP_ENV', 'production');
    setSystemConfigTestEnv('APP_DEBUG', 'false');
    setSystemConfigTestEnv('APP_LOCALE', 'fa');
    setSystemConfigTestEnv('APP_FALLBACK_LOCALE', 'en');
    setSystemConfigTestEnv('LOG_CHANNEL', 'runtime');
    setSystemConfigTestEnv('LOG_LEVEL', 'warning');
    SystemConfig::clearCache();

    expect(SystemConfig::get('pinoox', 'name'))->toBe('PinLab')
        ->and(SystemConfig::get('pinoox', 'mode'))->toBe('production')
        ->and(SystemConfig::get('pinoox', 'debug'))->toBeFalse()
        ->and(SystemConfig::get('pinoox', 'lang'))->toBe('fa')
        ->and(SystemConfig::get('pinoox', 'lang_fallback'))->toBe('en')
        ->and(SystemConfig::get('pinoox', 'log.channel'))->toBe('runtime')
        ->and(SystemConfig::get('pinoox', 'log.level'))->toBe('warning');
});

it('loads cache session and security config from env aliases', function () {
    setSystemConfigTestEnv('CACHE_STORE', 'file');
    setSystemConfigTestEnv('CACHE_PREFIX', 'pin_cache');
    setSystemConfigTestEnv('CACHE_PATH', '~storage/custom-cache');
    setSystemConfigTestEnv('SESSION_SAVE_PATH', '~storage/custom-sessions');
    setSystemConfigTestEnv('BCRYPT_ROUNDS', '10');
    SystemConfig::clearCache();

    expect(SystemConfig::get('cache', 'default'))->toBe('file')
        ->and(SystemConfig::get('cache', 'prefix'))->toBe('pin_cache')
        ->and(SystemConfig::get('cache', 'stores.file.path'))->toBe('~storage/custom-cache')
        ->and(SystemConfig::get('session', 'files'))->toBe('~storage/custom-sessions')
        ->and(SystemConfig::get('security', 'hashing.bcrypt.rounds'))->toBe(10);
});
