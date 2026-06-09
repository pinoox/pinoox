<?php

use Pinoox\Component\Package\App as PackageApp;
use Pinoox\Component\Router\RouteName;
use Pinoox\Component\Router\Router as RouterComponent;

it('registers fluent builder routes with methods, flow, data, tags, defaults, and priority', function () {
    $router = routerSystemRouter();

    $router->route('/posts/{id}', fn() => 'ok')
        ->put()
        ->name('posts.update')
        ->flow('auth')
        ->flow(['auth', 'verified'])
        ->data(['section' => 'blog'])
        ->tags(['posts', 'write'])
        ->defaults(['id' => 1])
        ->filters(['id' => '\d+'])
        ->priority(5)
        ->register();

    $route = $router->all()['posts.update'];
    $pinooxRoute = $route->getDefault('_router');

    expect($router->getAllPath()['posts.update'])->toBe('/posts/{id}')
        ->and($route->getMethods())->toBe(['PUT'])
        ->and($route->getDefault('id'))->toBe(1)
        ->and($route->getRequirements())->toBe(['id' => '\d+'])
        ->and($pinooxRoute->flows)->toBe(['auth', 'verified'])
        ->and($pinooxRoute->tags)->toBe(['posts', 'write'])
        ->and($pinooxRoute->getData())->toBe(['section' => 'blog'])
        ->and($pinooxRoute->getPriority())->toBe(5);
});

it('inherits collection path, name, flow, tags, data, defaults, and filters', function () {
    $router = routerSystemRouter();

    $router->collection(
        path: '/admin',
        routes: function (RouterComponent $router) {
            $router->route('/users/{id}', fn() => 'ok')
                ->get()
                ->name('users.show')
                ->flow('route')
                ->tags(['users'])
                ->data(['resource' => 'users'])
                ->defaults(['tab' => 'profile'])
                ->filters(['id' => '\d+'])
                ->register();
        },
        defaults: ['locale' => 'en'],
        filters: ['slug' => '[a-z]+'],
        prefixName: 'admin.',
        data: ['area' => 'admin'],
        flows: ['auth'],
        tags: ['admin'],
    );

    $route = $router->all()['admin.users.show'];
    $pinooxRoute = $route->getDefault('_router');

    expect($router->getAllPath()['admin.users.show'])->toBe('/admin/users/{id}')
        ->and($route->getMethods())->toBe(['GET'])
        ->and($route->getDefault('locale'))->toBe('en')
        ->and($route->getDefault('tab'))->toBe('profile')
        ->and($route->getRequirements())->toBe([
            'slug' => '[a-z]+',
            'id' => '\d+',
        ])
        ->and($pinooxRoute->flows)->toBe(['auth', 'route'])
        ->and($pinooxRoute->tags)->toBe(['admin', 'users'])
        ->and($pinooxRoute->getData())->toBe([
            'area' => 'admin',
            'resource' => 'users',
        ]);
});

it('generates paths and matches registered routes', function () {
    $router = routerSystemRouter();

    $router->route('/posts/{id}', fn() => 'ok')
        ->get()
        ->name('posts.show')
        ->filters(['id' => '\d+'])
        ->register();

    $matched = $router->match('/posts/15');

    expect($router->path('posts.show', ['id' => 15]))->toBe('/posts/15')
        ->and($matched['_route'])->toBe('posts.show')
        ->and($matched['id'])->toBe('15');
});

it('loads nested route files relative to the current route file directory', function () {
    $basePath = testFixtures('router_system_app');
    routerSystemDeleteDirectory($basePath);

    mkdir($basePath . '/routes/web', 0777, true);
    file_put_contents($basePath . '/routes/web.php', <<<'PHP'
<?php

$this->collection('/test', 'web/test.php');
PHP);
    file_put_contents($basePath . '/routes/web/test.php', <<<'PHP'
<?php

$this->route('/items', fn() => 'ok')->get()->name('test.items')->register();
PHP);

    try {
        $router = routerSystemRouter(basePath: $basePath);

        $router->collection(routes: $basePath . '/routes/web.php');

        expect($router->getAllPath()['test.items'])->toBe('/test/items');
    } finally {
        routerSystemDeleteDirectory($basePath);
    }
});

it('loads route manifest arrays from returned config', function () {
    $basePath = testFixtures('router_manifest_app');
    routerSystemDeleteDirectory($basePath);

    mkdir($basePath . '/routes', 0777, true);
    file_put_contents($basePath . '/routes/web.php', <<<'PHP'
<?php

return [
    'routes' => [
        [
            'method' => 'GET',
            'path' => '/manifest',
            'action' => fn() => 'manifest',
            'name' => 'manifest.home',
        ],
    ],
];
PHP);

    try {
        $router = routerSystemRouter(basePath: $basePath);
        $router->collection(routes: $basePath . '/routes/web.php');

        expect($router->getAllPath()['manifest.home'])->toBe('/manifest');
    } finally {
        routerSystemDeleteDirectory($basePath);
    }
});

it('registers routes through routes() builder callback', function () {
    $router = routerSystemRouter();

    $router->routes(function ($r) {
        $r->get('/builder', fn() => 'builder')->name('builder.home');
    });

    expect($router->getAllPath()['builder.home'])->toBe('/builder');
});

it('prefixes short route names with the collection namespace', function () {
    $router = routerSystemRouter();

    $router->collection(
        prefixName: 'installer.',
        routes: function (RouterComponent $router) {
            $router->route('/ping', fn () => 'ok')->get()->name('ping');
        },
    );

    expect($router->getAllPath()['installer.ping'])->toBe('/ping');
});

it('auto-registers fluent routes when register is omitted', function () {
    $router = routerSystemRouter();

    (function () use ($router) {
        $router->route('/auto', fn() => 'ok')->get()->name('auto.home');
    })();

    expect($router->getAllPath()['auto.home'])->toBe('/auto');
});

it('collects api-style route entries through route helpers', function () {
    $entries = \Pinoox\Router\collect(function () {
        \Pinoox\Router\post('/auth/login', fn() => 'ok')
            ->name('auth.login')
            ->tag('Authentication')
            ->summary('Login');
    });

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['method'])->toBe('POST')
        ->and($entries[0]['path'])->toBe('/auth/login')
        ->and($entries[0]['name'])->toBe('auth.login')
        ->and($entries[0]['tag'])->toBe('Authentication')
        ->and($entries[0]['summary'])->toBe('Login');
});

it('builds actions from the active collection without runtime warnings', function () {
    $router = routerSystemRouter();

    $router->route('/ping', fn () => 'pong')->get()->name('ping')->register();

    $built = $router->buildAction(fn () => 'pong');

    expect($built)->toBeInstanceOf(Closure::class);
});

function routerSystemRouter(string $package = 'com_test_router', ?string $basePath = null): RouterComponent
{
    return routerSystemRouterWithPath($package, $basePath ?? testProjectRoot());
}

function routerSystemRouterWithPath(string $package, string $basePath): RouterComponent
{
    $app = test()->createMock(PackageApp::class);
    $app->method('package')->willReturn($package);
    $app->method('path')->willReturnCallback(
        static fn(string $path = '') => rtrim($basePath, '/\\') . '/' . ltrim($path, '/\\')
    );

    return new RouterComponent(new RouteName(), $app);
}

function routerSystemDeleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
}

