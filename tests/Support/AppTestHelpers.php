<?php

use Pinoox\Component\Test\AppTestKit;

function pinooxBoot(): void
{
    AppTestKit::boot();
}

function appPackage(?string $package = null): string
{
    if ($package !== null) {
        AppTestKit::setPackage($package);

        return $package;
    }

    return AppTestKit::package();
}

function inApp(string $package, Closure $callback, string $path = '/'): mixed
{
    return AppTestKit::inApp($package, $callback, $path);
}

function appPath(string $package, string $subPath = ''): string
{
    return AppTestKit::path($package, $subPath);
}

function appRequest(string $method, string $uri, array $data = [], array $query = [], array $headers = [], ?array $json = null): Pinoox\Component\Http\Request
{
    return AppTestKit::request($method, $uri, $data, $query, $headers, $json);
}

function appCall(string $package, string $method, string $uri, array $options = []): Pinoox\Component\Test\TestResponse
{
    return AppTestKit::call($package, $method, $uri, $options);
}

function appGet(string $package, string $uri, array $query = [], array $headers = []): Pinoox\Component\Test\TestResponse
{
    return AppTestKit::get($package, $uri, $query, $headers);
}

function appPost(string $package, string $uri, array $data = [], array $headers = []): Pinoox\Component\Test\TestResponse
{
    return AppTestKit::post($package, $uri, $data, $headers);
}

function appPostJson(string $package, string $uri, array $json = [], array $headers = []): Pinoox\Component\Test\TestResponse
{
    return AppTestKit::postJson($package, $uri, $json, $headers);
}

function fakeApp(string $package, array $files = []): string
{
    return AppTestKit::fakeApp($package, $files);
}

function deleteFakeApp(string $package): void
{
    AppTestKit::deleteFakeApp($package);
}

function cleanupTestArtifacts(): void
{
    AppTestKit::cleanupTransientArtifacts();
}

function expectPortalContract(string $class): void
{
    $basePath = str_replace('\\', '/', dirname(__DIR__, 2) . '/pincore/');
    $file = $basePath . str_replace('\\', '/', substr($class, strlen('Pinoox\\'))) . '.php';
    $source = file_get_contents($file);

    expect(is_file($file))->toBeTrue()
        ->and($source)->toContain('extends Portal')
        ->and($source)->toContain('function __name');
}

