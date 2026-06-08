<?php

use Pinoox\Component\Access\AccessConfig;
use Pinoox\Component\Access\Manager;
use Pinoox\Component\Router\RouteManifest;
use Pinoox\Portal\Access;
use Pinoox\Model\UserModel;

it('registers access portal and helpers', function () {
    expect(class_exists(Access::class))->toBeTrue()
        ->and(function_exists('can'))->toBeTrue()
        ->and(function_exists('cannot'))->toBeTrue();
});

it('resolves access config defaults', function () {
    $config = AccessConfig::resolve();

    expect($config)->toHaveKeys(['enabled', 'package', 'super_roles', 'groups'])
        ->and($config['enabled'])->toBeTrue()
        ->and($config['super_roles'])->toContain('admin');
});

it('checks abilities via custom gates', function () {
    $manager = new Manager();

    $user = new UserModel();
    $user->user_id = 99;
    $user->group_key = 'guest';

    Access::define('posts.edit', fn (UserModel $u) => $u->group_key === 'editor');

    expect($manager->can('posts.edit', $user))->toBeFalse();

    $user->group_key = 'editor';

    expect($manager->can('posts.edit', $user))->toBeTrue();
});

it('matches wildcard group permissions from app config', function () {
    $manager = new Manager();

    $user = new UserModel();
    $user->user_id = 100;
    $user->group_key = 'editor';

    Access::define('__noop__', fn () => false);

    \Pinoox\Portal\App\App::set('access', [
        'groups' => [
            'editor' => ['blog.*'],
        ],
    ]);

    expect($manager->can('blog.posts.view', $user))->toBeTrue()
        ->and($manager->can('shop.orders.view', $user))->toBeFalse();
});

it('treats super roles as full access', function () {
    $manager = new Manager();

    $user = new UserModel();
    $user->user_id = 101;
    $user->group_key = 'admin';

    expect($manager->can('anything.secret', $user))->toBeTrue();
});

it('denies guests when permission is required', function () {
    $manager = new Manager();

    expect($manager->can('manager.users.view'))->toBeFalse();
});

it('injects permission flow into route manifest entries', function () {
    $entry = RouteManifest::normalizeEntry([
        'path' => '/users',
        'action' => 'UserController@index',
        'permission' => 'manager.users.view',
        'flow' => ['manager.auth'],
    ]);

    expect($entry['permission'])->toBe('manager.users.view')
        ->and($entry['data']['permission'])->toBe('manager.users.view')
        ->and($entry['flow'])->toContain('permission')
        ->and($entry['flow'])->toContain('manager.auth');
});

it('appends permission flow only once', function () {
    $flow = RouteManifest::withPermissionFlow(['manager.auth', 'permission'], 'manager.users.view');

    expect($flow)->toBe(['manager.auth', 'permission']);
});

it('extracts route permission from api defaults', function () {
    $manager = new Manager();

    $permission = $manager->routePermission(null, [
        '_api_permission' => 'manager.users.view',
    ]);

    expect($permission)->toBe('manager.users.view');
});

it('authorizes or throws authorization exception', function () {
    $manager = new Manager();

    $user = new UserModel();
    $user->user_id = 102;
    $user->group_key = 'admin';

    $manager->authorize('manager.users.view', $user);

    expect(true)->toBeTrue();
});

