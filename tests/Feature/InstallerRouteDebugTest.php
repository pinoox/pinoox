<?php

use Pinoox\Component\Http\Request;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppProvider;

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

it('normalizes path info for mamp subdirectory installs', function () {
    $request = installerSubdirRequest('/pinoox/user');

    expect($request->getPathInfo())->toBe('/user')
        ->and($request->getBasePath())->toBe('/pinoox');
});

it('matches installer routes with mamp subdirectory base path', function () {
    pinooxBoot();
    AppEngine::__rebuild();

    $request = installerSubdirRequest('/pinoox/user');

    AppProvider::meetingHandle('com_pinoox_installer', '/', $request);

    inApp('com_pinoox_installer', function () use ($request) {
        $match = App::router()->matchRequest($request);
        expect($match['_route'])->toBe('installer.user.redirect');
    });
});

it('matches installer api ping under subdirectory', function () {
    pinooxBoot();
    AppEngine::__rebuild();

    $request = installerSubdirRequest('/pinoox/api/v1/ping');

    AppProvider::meetingHandle('com_pinoox_installer', '/', $request);

    inApp('com_pinoox_installer', function () use ($request) {
        $match = App::router()->matchRequest($request);
        expect($match['_route'])->toEndWith('.ping');
    });
});
