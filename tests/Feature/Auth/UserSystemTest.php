<?php

use Pinoox\Component\User\AuthConfig;
use Pinoox\Component\User\Guard;
use Pinoox\Component\User\LoginResult;
use Pinoox\Component\User\Manager;
use Pinoox\Component\User\UserProvider;
use Pinoox\Portal\Auth;
use Pinoox\Portal\User;

it('resolves auth config modes', function () {
    expect(AuthConfig::MODE_JWT)->toBe('jwt')
        ->and(AuthConfig::MODE_COOKIE)->toBe('cookie')
        ->and(AuthConfig::MODE_SESSION)->toBe('session');
});

it('exposes profile and password validation rules', function () {
    $manager = new Manager();

    expect($manager->profileRules(1))->toHaveKeys(['fname', 'lname', 'email', 'username'])
        ->and($manager->passwordRules())->toHaveKeys(['old_password', 'new_password', 'valid_password']);
});

it('registers auth and user portals on the same manager', function () {
    expect(class_exists(Auth::class))->toBeTrue()
        ->and(class_exists(User::class))->toBeTrue();

    Auth::boot();

    expect(Auth::guest())->toBeTrue()
        ->and(Auth::check())->toBeFalse();
});

it('builds structured login results', function () {
    $result = LoginResult::fail('invalid_credentials', 'wrong password');

    expect($result->success)->toBeFalse()
        ->and($result->reason)->toBe('invalid_credentials')
        ->and($result->message)->toBe('wrong password');
});

it('exposes auth session transport through the user portal', function () {
    expect(method_exists(\Pinoox\Component\User\AuthSession::class, 'isLoggedIn'))->toBeTrue()
        ->and(method_exists(\Pinoox\Component\User\Manager::class, 'get'))->toBeTrue()
        ->and(class_exists(User::class))->toBeTrue();
});

it('maps helper functions to auth portal', function () {
    expect(function_exists('user'))->toBeTrue()
        ->and(function_exists('auth'))->toBeTrue()
        ->and(function_exists('isLogin'))->toBeTrue();
});

it('uses guard guest and check helpers', function () {
    $guard = new Guard(new UserProvider());
    $guard->boot();

    expect($guard->guest())->toBeTrue()
        ->and($guard->check())->toBeFalse();
});

