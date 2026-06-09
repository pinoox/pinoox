<?php

use Pinoox\Component\AppEvent\AppRouteRegistry;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Router\Action\ActionRegistry;
use Pinoox\Component\Router\RouteSourceRegistry;
use Pinoox\PinDoc\Api\AppApiServiceProvider;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppProvider;

beforeEach(function () {
    ActionRegistry::reset();
    AppRouteRegistry::reset();
    RouteSourceRegistry::reset();
});

function installerSubdirRequest(string $uri): Request
{
    return Request::create(
        $uri,
        'GET',
        [],
        [],
        [],
        [
            'SCRIPT_NAME' => '/pinoox/index.php',
            'REQUEST_URI' => $uri,
        ],
    );
}

it('resolves subdirectory path info and matches installer api ping', function () {
    pinooxBoot();
    AppEngine::__rebuild();

    $request = installerSubdirRequest('/pinoox/api/v1/ping');

    expect($request->getPathInfo())->toBe('/api/v1/ping')
        ->and($request->getBasePath())->toBe('/pinoox');

    inApp('com_pinoox_installer', function () use ($request) {
        AppApiServiceProvider::resetState();
        AppApiServiceProvider::register();

        $match = App::router()->matchRequest($request);
        expect($match['_route'])->toEndWith('.ping');
    });
});

it('matches installer routes with mamp subdirectory base path', function () {
    pinooxBoot();
    AppEngine::__rebuild();
    \Pinoox\Portal\Router::__rebuild();

    $request = installerSubdirRequest('/pinoox/user');

    expect($request->getPathInfo())->toBe('/user');

    AppProvider::meetingHandle('com_pinoox_installer', '/', $request);

    inApp('com_pinoox_installer', function () use ($request) {
        $match = App::router()->matchRequest($request);
        expect($match['_route'])->toBe('installer.user.redirect');
    });
});
