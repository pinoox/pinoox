<?php

use Pinoox\Component\Kernel\Boot\BootPipeline;
use Pinoox\Component\Kernel\Container\AppServiceContainer;
use Pinoox\Component\Kernel\Container\IlluminateBridge;
use Pinoox\Component\Kernel\Container\ServiceContainerBootstrap;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Test\AppTestKit;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Kernel\Boot;

beforeEach(function () {
    Loader::setBasePath(testProjectRoot());
    AppTestKit::boot();
});

it('exposes a deterministic boot pipeline', function () {
    $stages = AppProvider::___()->bootStages();

    expect($stages)->toBe([
        'composer',
        'loader',
        'boot.global',
        'app.boot',
        'container',
        'events',
        'database',
        'api',
        'session',
    ])->and(Boot::bootStages())->toBe($stages);
});

it('keeps app container opt-in disabled by default', function () {
    expect(ServiceContainerBootstrap::containerEnabled('com_pinoox_welcome'))->toBeFalse();
});

it('registers bindings when container is enabled', function () {
    $interface = 'Tests\\Support\\KernelSampleContract';
    $service = 'Tests\\Support\\KernelSampleService';

    IlluminateBridge::bind($interface, $service);

    $instance = IlluminateBridge::make($interface);

    expect($instance)->toBeInstanceOf($service)
        ->and($instance->label())->toBe('kernel-sample');
});

it('discovers controller classes for an app package', function () {
    $controllers = AppServiceContainer::discoverControllers('com_pinoox_welcome');

    expect($controllers)->toBeArray();
});

it('builds boot pipeline for a context', function () {
    $provider = AppProvider::___();

    expect($provider->bootStages())->toContain('app.boot', 'container');
});

it('registers service_container alias in pincore container', function () {
    $builder = ServiceContainerBootstrap::boot('~');

    expect($builder->has('kernel.service_container'))->toBeTrue()
        ->and($builder->hasAlias('service_container'))->toBeTrue();
});

it('hydrates container bindings from boot cache payload shape', function () {
    $payload = [
        'bindings' => [
            Tests\Support\KernelSampleContract::class => Tests\Support\KernelSampleService::class,
        ],
        'controllers' => [],
        'singletons' => [],
    ];

    AppServiceContainer::hydrate('com_test_cache', $payload);

    expect(AppServiceContainer::export('com_test_cache'))->toBe($payload);
});

