<?php

use Pinoox\Component\Cookie;
use Pinoox\Component\User\AuthConfig;
use Pinoox\Component\User\AuthSession;

afterEach(function () {
    AuthSession::reset();
    AuthConfig::reset();
    $_COOKIE = [];
});

it('persists a jwt client token into the auth cookie', function () {
    AuthSession::applyConfig([
        'mode' => AuthConfig::MODE_JWT,
        'key' => 'manager_pinoox',
        'lifetime' => 1,
        'lifetime_unit' => 'day',
        'jwt_secret' => 'test-secret',
    ]);

    AuthSession::persistClientJwt('eyJ.test.token');

    expect(Cookie::get('manager_pinoox'))->toBe('eyJ.test.token');
});
