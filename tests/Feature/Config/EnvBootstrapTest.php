<?php

use Pinoox\Component\Helpers\EnvBootstrap;
use Pinoox\Support\SystemConfig;

afterEach(function () {
    foreach (['APP_ENV', 'APP_DEBUG', 'PINOOX_EXCEPTION', 'MODE'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
    EnvBootstrap::reset();
});

it('defaults to production and debug false when .env file is empty', function () {
    $dir = testProjectRoot() . '/tests/Fixtures/env_bootstrap_empty';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . '/.env', '');

    foreach (['APP_ENV', 'APP_DEBUG', 'PINOOX_EXCEPTION', 'MODE'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    EnvBootstrap::reset();
    EnvBootstrap::load($dir);
    SystemConfig::clearCache();

    expect(SystemConfig::env('APP_ENV'))->toBe('production')
        ->and(SystemConfig::env('MODE'))->toBe('production')
        ->and(SystemConfig::env('APP_DEBUG'))->toBeFalse()
        ->and(SystemConfig::env('PINOOX_EXCEPTION'))->toBeTrue();

    $pinoox = SystemConfig::get('pinoox');
    expect($pinoox['mode'])->toBe('production')
        ->and($pinoox['debug'])->toBeFalse()
        ->and($pinoox['exception'])->toBeTrue();
});

it('accepts MODE as alias when APP_ENV is absent', function () {
    $dir = testProjectRoot() . '/tests/Fixtures/env_bootstrap_mode_alias';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . '/.env', "MODE=dev\n");

    foreach (['APP_ENV', 'APP_DEBUG', 'PINOOX_EXCEPTION', 'MODE'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    EnvBootstrap::reset();
    EnvBootstrap::load($dir);
    SystemConfig::clearCache();

    expect(SystemConfig::env('APP_ENV'))->toBe('development')
        ->and(SystemConfig::env('MODE'))->toBe('development')
        ->and(runtime_env_mode())->toBe('development');
});

it('normalizes APP_ENV aliases like dev to development', function () {
    $dir = testProjectRoot() . '/tests/Fixtures/env_bootstrap_dev_alias';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . '/.env', "APP_ENV=dev\n");

    foreach (['APP_ENV', 'APP_DEBUG', 'PINOOX_EXCEPTION', 'MODE'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    EnvBootstrap::reset();
    EnvBootstrap::load($dir);
    SystemConfig::clearCache();

    expect(SystemConfig::env('APP_ENV'))->toBe('development')
        ->and(SystemConfig::env('MODE'))->toBe('development');
});

it('prefers APP_ENV when both APP_ENV and MODE are set', function () {
    $dir = testProjectRoot() . '/tests/Fixtures/env_bootstrap_mode_conflict';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . '/.env', "APP_ENV=production\nMODE=development\n");

    foreach (['APP_ENV', 'APP_DEBUG', 'PINOOX_EXCEPTION', 'MODE'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    EnvBootstrap::reset();
    EnvBootstrap::load($dir);
    SystemConfig::clearCache();

    expect(SystemConfig::env('APP_ENV'))->toBe('production')
        ->and(SystemConfig::env('MODE'))->toBe('production');
});

it('respects APP_DEBUG when explicitly set in .env', function () {
    $dir = testProjectRoot() . '/tests/Fixtures/env_bootstrap_explicit';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . '/.env', "APP_ENV=development\nAPP_DEBUG=true\n");

    EnvBootstrap::reset();
    EnvBootstrap::load($dir);

    expect(SystemConfig::env('APP_ENV'))->toBe('development')
        ->and(SystemConfig::env('APP_DEBUG'))->toBeTrue();
});

it('defaults debug true for development when APP_DEBUG is not in .env', function () {
    $dir = testProjectRoot() . '/tests/Fixtures/env_bootstrap_dev_no_debug';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . '/.env', "APP_ENV=development\n");

    EnvBootstrap::reset();
    EnvBootstrap::load($dir);
    SystemConfig::clearCache();

    expect(SystemConfig::env('APP_ENV'))->toBe('development')
        ->and(SystemConfig::env('APP_DEBUG'))->toBeTrue();
});

it('respects explicit APP_DEBUG=false in development', function () {
    $dir = testProjectRoot() . '/tests/Fixtures/env_bootstrap_dev_debug_false';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . '/.env', "APP_ENV=development\nAPP_DEBUG=false\n");

    EnvBootstrap::reset();
    EnvBootstrap::load($dir);

    expect(SystemConfig::env('APP_ENV'))->toBe('development')
        ->and(SystemConfig::env('APP_DEBUG'))->toBeFalse();
});

it('defaults debug true when MODE alias resolves to a non-production mode', function () {
    $dir = testProjectRoot() . '/tests/Fixtures/env_bootstrap_mode_debug_default';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . '/.env', "MODE=dev\n");

    foreach (['APP_ENV', 'APP_DEBUG', 'PINOOX_EXCEPTION', 'MODE'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    EnvBootstrap::reset();
    EnvBootstrap::load($dir);

    expect(SystemConfig::env('APP_ENV'))->toBe('development')
        ->and(SystemConfig::env('APP_DEBUG'))->toBeTrue();
});
