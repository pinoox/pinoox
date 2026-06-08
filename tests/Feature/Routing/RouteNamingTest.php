<?php

use Pinoox\Component\Router\RouteNaming;

it('derives route prefix from package slug', function () {
    expect(RouteNaming::prefixForPackage('com_pinoox_installer'))->toBe('installer.')
        ->and(RouteNaming::prefixForPackage('com_pinoox_comingsoon'))->toBe('comingsoon.');
});

it('resolves full route names for cross-app references', function () {
    expect(RouteNaming::full('home', 'com_pinoox_installer'))->toBe('installer.home')
        ->and(RouteNaming::full('installer.home', 'com_pinoox_installer'))->toBe('installer.home');
});

it('prefixes dotted local route names for the active app', function () {
    expect(RouteNaming::full('app.run', 'com_pinoox_manager'))->toBe('manager.app.run')
        ->and(RouteNaming::full('app.run.sub', 'com_pinoox_manager'))->toBe('manager.app.run.sub')
        ->and(RouteNaming::full('manager.app.run', 'com_pinoox_manager'))->toBe('manager.app.run');
});

it('exposes route_name helper', function () {
    expect(\Pinoox\Router\route_name('home', 'com_pinoox_manager'))->toBe('manager.home');
});

