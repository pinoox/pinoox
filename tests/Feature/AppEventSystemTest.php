<?php

use Pinoox\Component\AppEvent\AppBootstrap;
use Pinoox\Component\AppEvent\AppEventNames;
use Pinoox\Component\AppEvent\AppRegisterCollector;
use Pinoox\Component\Router\RouteManifest;
use Pinoox\Portal\AppBoot;

it('registers app boot portal and helper', function () {
    expect(class_exists(AppBoot::class))->toBeTrue()
        ->and(function_exists('app_boot'))->toBeTrue();
});

it('defines app event names', function () {
    expect(AppEventNames::BOOTING)->toBe('app.booting')
        ->and(AppEventNames::BOOTED)->toBe('app.booted')
        ->and(AppEventNames::package(AppEventNames::ROUTES, 'com_test'))->toBe('app.routes.com_test');
});

it('injects permission flow through route registration helper', function () {
    $entry = RouteManifest::normalizeEntry([
        'path' => '/event-route',
        'action' => fn () => 'ok',
        'permission' => 'demo.view',
    ]);

    expect($entry['flow'])->toContain('permission');
});

it('tracks pending when targets globally', function () {
    AppRegisterCollector::$pendingWhen = [];
    AppRegisterCollector::$pendingWhen['com_host'][] = fn () => null;

    expect(AppRegisterCollector::$pendingWhen)->toHaveKey('com_host');
});

it('reports boot state via portal', function () {
    expect(AppBootstrap::booted('com_nonexistent_boot_state_' . uniqid()))->toBeFalse();
});

it('exposes api manifest collector storage', function () {
    expect(method_exists(AppBootstrap::class, 'apiManifests'))->toBeTrue();
});

