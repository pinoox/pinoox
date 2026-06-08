<?php

use Pinoox\Component\Helpers\EnvFile;
use Pinoox\Support\SystemConfig;

beforeEach(function () {
    SystemConfig::clearCache();
});

afterEach(function () {
    $path = envFileTestPath();

    if (is_file($path)) {
        unlink($path);
    }

    foreach (['DB_DATABASE', 'DB_HOST', 'DB_PASSWORD', 'DB_STRICT'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    SystemConfig::clearCache();
});

it('updates existing env keys and appends missing ones', function () {
    $path = envFileTestPath();
    file_put_contents($path, "DB_DATABASE=old\nAPP_NAME=Pinoox\n");

    $file = new EnvFile($path);

    expect($file->setMany([
        'DB_DATABASE' => 'pin',
        'DB_HOST' => '127.0.0.1',
    ]))->toBeTrue();

    $content = (string) file_get_contents($path);

    expect($content)
        ->toContain('DB_DATABASE=pin')
        ->toContain('DB_HOST=127.0.0.1')
        ->toContain('APP_NAME=Pinoox')
        ->not->toContain('DB_DATABASE=old');
});

it('quotes values that need escaping', function () {
    $path = envFileTestPath();
    $file = new EnvFile($path);

    $file->setMany([
        'DB_PASSWORD' => 'pa ss#word',
        'DB_STRICT' => true,
    ]);

    $content = (string) file_get_contents($path);

    expect($content)
        ->toContain('DB_PASSWORD="pa ss#word"')
        ->toContain('DB_STRICT=true');
});

it('applies variables to the runtime environment', function () {
    $path = envFileTestPath();
    $file = new EnvFile($path);

    $file->applyToRuntime([
        'DB_DATABASE' => 'runtime_db',
        'DB_STRICT' => false,
    ]);

    expect(env('DB_DATABASE'))->toBe('runtime_db')
        ->and(env('DB_STRICT'))->toBeFalse();
});

function envFileTestPath(): string
{
    $path = str_replace('\\', '/', testProjectRoot() . '/tests/Fixtures/env_file/.env');
    $dir = dirname($path);

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $path;
}

