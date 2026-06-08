<?php

use Pinoox\Component\Transport\TransportConfig;
use Pinoox\Component\Transport\TransportScenario;
use Pinoox\Component\User\AuthConfig;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;

it('resolves platform user transport for comingsoon and manager', function () {
    $comingsoon = AppEngine::config('com_pinoox_comingsoon')->get('transport');
    $manager = AppEngine::config('com_pinoox_manager')->get('transport');

    expect($comingsoon)->toBeArray()
        ->and($comingsoon['user'] ?? null)->toBe('platform')
        ->and($manager['user'] ?? null)->toBe('platform');
});

it('shares auth between manager host and comingsoon guest via platform transport', function () {
    expect(TransportConfig::authSourceForPackage('com_pinoox_comingsoon', 'com_pinoox_manager'))
        ->toBe('com_pinoox_manager')
        ->and(TransportConfig::authSourceForPackage('com_pinoox_manager', 'com_pinoox_manager'))
        ->toBe('com_pinoox_manager')
        ->and(TransportConfig::sharesAuthWith('com_pinoox_comingsoon', 'com_pinoox_manager'))
        ->toBeTrue();
});

it('resolves manager jwt auth config when active app is comingsoon', function () {
    App::setLayer(new \Pinoox\Component\Package\AppLayer('/', 'com_pinoox_comingsoon'));

    AuthConfig::reset();

    $config = AuthConfig::resolve(refresh: true);

    expect($config['source'])->toBe('com_pinoox_manager')
        ->and($config['mode'])->toBe('jwt')
        ->and($config['key'])->toBe('manager_pinoox')
        ->and($config['provider'])->toBe(TransportConfig::PLATFORM);

    AuthConfig::reset();
});

it('expands user scenario to auth granular keys', function () {
    expect(TransportConfig::authSourceForPackage('com_pinoox_comingsoon', 'com_pinoox_manager'))
        ->toBe('com_pinoox_manager');

    App::setLayer(new \Pinoox\Component\Package\AppLayer('/', 'com_pinoox_comingsoon'));

    expect(TransportConfig::package(TransportScenario::USER_TABLE))->toBe(TransportConfig::PLATFORM)
        ->and(TransportConfig::package(TransportScenario::SESSION_TOKEN))->toBe(TransportConfig::PLATFORM)
        ->and(TransportConfig::scopeValues(TransportScenario::USER_TABLE))
        ->toBe(['platform', 'com_pinoox_manager']);
});
