<?php

use Pinoox\Component\Http\Request;
use Pinoox\Component\Package\AppRouter;
use Pinoox\Component\Package\Routing\AppRouteMatcher;
use Pinoox\Component\Package\Routing\Domain;

afterEach(function () {
    Domain::reset();
});

it('matches the longest stable path prefix', function () {
    $routes = [
        '/manager' => 'com_pinoox_manager',
        '/manager/settings' => 'com_pinoox_settings',
        '/' => 'com_pinoox_welcome',
    ];

    expect(AppRouteMatcher::match('manager/settings/profile', $routes))
        ->toBe(['path' => '/manager/settings', 'package' => 'com_pinoox_settings'])
        ->and(AppRouteMatcher::match('manager/dashboard', $routes))
        ->toBe(['path' => '/manager', 'package' => 'com_pinoox_manager'])
        ->and(AppRouteMatcher::match('/', $routes))
        ->toBeNull();
});

it('normalizes route paths when saving router mappings', function () {
    expect(AppRouteMatcher::normalize('shop/'))->toBe('/shop')
        ->and(AppRouteMatcher::normalize('/shop'))->toBe('/shop')
        ->and(AppRouteMatcher::normalize(''))->toBe('/');
});

it('resolves exact and wildcard host mappings', function () {
    Domain::useConfig([
        'default' => 'https://example.com',
        'hosts' => [
            'shop.example.com' => 'com_my_shop',
            '*.example.com' => [
                'package' => 'com_tenant',
            ],
        ],
    ]);

    expect(Domain::match('shop.example.com')?->package)->toBe('com_my_shop')
        ->and(Domain::match('tenant.example.com')?->package)->toBe('com_tenant')
        ->and(Domain::match('tenant.example.com')?->subdomain)->toBe('tenant')
        ->and(Domain::match('unknown.test'))->toBeNull()
        ->and(Domain::isDefaultHost('unknown.test'))->toBeTrue()
        ->and(Domain::isDefaultHost('shop.example.com'))->toBeFalse()
        ->and(Domain::isCanonicalDefaultHost('example.com'))->toBeTrue()
        ->and(Domain::isCanonicalDefaultHost('shop.example.com'))->toBeFalse();
});

it('treats unmapped hosts as default domain for path routing', function () {
    Domain::useConfig([
        'default' => 'example.com',
        'hosts' => [
            'shop.example.com' => 'com_my_shop',
        ],
    ]);

    $request = Request::create('http://localhost/shop', 'GET', [], [], [], [
        'HTTP_HOST' => 'localhost',
    ]);

    $router = appRouterSystemTestMakeRouter($request, [
        '/shop' => 'com_my_shop',
        '/' => 'com_pinoox_installer',
    ]);

    $layer = $router->find('shop');

    expect($layer->getPackageName())->toBe('com_my_shop')
        ->and($layer->matchedBy())->toBe('default_domain')
        ->and($layer->isDefaultDomain())->toBeTrue()
        ->and($layer->isCanonicalDefault())->toBeFalse();
});

it('prefers domain routing before path routing in AppRouter', function () {
    Domain::useConfig([
        'hosts' => [
            'manager.localhost' => 'com_pinoox_manager',
        ],
    ]);

    $request = Request::create(
        'http://manager.localhost/welcome/page',
        'GET',
        [],
        [],
        [],
        ['HTTP_HOST' => 'manager.localhost'],
    );

    $router = appRouterSystemTestMakeRouter($request, [
        '/welcome' => 'com_pinoox_welcome',
        '/' => 'com_pinoox_installer',
    ]);

    $layer = $router->find();

    expect($layer->getPackageName())->toBe('com_pinoox_manager')
        ->and($layer->getPath())->toBe('/')
        ->and($layer->matchedBy())->toBe('domain')
        ->and($layer->host())->toBe('manager.localhost');
});

it('falls back to root and wildcard path routes', function () {
    $request = Request::create('http://localhost/unknown', 'GET');

    $router = appRouterSystemTestMakeRouter($request, [
        '/' => 'com_pinoox_installer',
        '*' => 'com_pinoox_welcome',
    ]);

    expect($router->find('unknown')->getPackageName())->toBe('com_pinoox_welcome')
        ->and($router->find('unknown')->matchedBy())->toBe('default_domain')
        ->and($router->find('unknown')->isDefaultDomain())->toBeTrue()
        ->and($router->find('/')->getPackageName())->toBe('com_pinoox_welcome')
        ->and($router->find('/')->matchedBy())->toBe('default_domain');
});

it('uses root mapping as the default app for unmatched paths', function () {
    $request = Request::create('http://localhost/pinoox/dist/pinoox.js', 'GET', [], [], [], [
        'SCRIPT_NAME' => '/pinoox/index.php',
        'REQUEST_URI' => '/pinoox/dist/pinoox.js',
    ]);

    $router = appRouterSystemTestMakeRouter($request, [
        '/' => 'com_pinoox_installer',
    ]);

    expect($router->find()->getPackageName())->toBe('com_pinoox_installer')
        ->and($router->find()->matchedBy())->toBe('default_domain');
});

it('skips disabled apps when resolving path routes', function () {
    $request = Request::create('http://localhost/manager', 'GET');

    $router = appRouterSystemTestMakeRouter($request, [
        '/manager' => 'com_disabled_app',
        '/' => 'com_pinoox_installer',
    ], [
        'com_disabled_app' => false,
        'com_pinoox_installer' => true,
    ]);

    expect($router->find('manager')->getPackageName())->toBe('com_pinoox_installer')
        ->and($router->find('manager')->matchedBy())->toBe('default_domain')
        ->and($router->find('')->getPackageName())->toBe('com_pinoox_installer')
        ->and($router->find('')->matchedBy())->toBe('default_domain');
});

function appRouterSystemTestMakeRouter(Request $request, array $routes, array $enabled = []): AppRouter
{
    $engine = new AppRouterSystemTestEngine($enabled);

    return new AppRouter(new AppRouterSystemTestConfig($routes), $engine, $request);
}

class AppRouterSystemTestConfig implements \Pinoox\Component\Store\Config\ConfigInterface
{
    public function __construct(private array $data = [])
    {
    }

    public function get(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function add(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function save(): static
    {
        return $this;
    }

    public function setData(mixed $data): static
    {
        $this->data = is_array($data) ? $data : [];

        return $this;
    }

    public function remove(string $key): static
    {
        unset($this->data[$key]);

        return $this;
    }
}

class AppRouterSystemTestEngine implements \Pinoox\Component\Package\Engine\EngineInterface
{
    public function __construct(private array $enabled = [])
    {
    }

    public function config(string|\Pinoox\Component\Package\Reference\ReferenceInterface $packageName): \Pinoox\Component\Store\Config\ConfigInterface
    {
        $package = is_string($packageName) ? $packageName : $packageName->getPackageName();

        return new AppRouterSystemTestConfig([
            'enable' => $this->enabled[$package] ?? true,
        ]);
    }

    public function lang(string|\Pinoox\Component\Package\Reference\ReferenceInterface $packageName): \Pinoox\Component\Translator\Translator
    {
        throw new RuntimeException('Not needed in this test.');
    }

    public function router(string|\Pinoox\Component\Package\Reference\ReferenceInterface $packageName, string $path = ''): \Pinoox\Component\Router\Router
    {
        throw new RuntimeException('Not needed in this test.');
    }

    public function exists(string|\Pinoox\Component\Package\Reference\ReferenceInterface $packageName): bool
    {
        return true;
    }

    public function stable(string|\Pinoox\Component\Package\Reference\ReferenceInterface $packageName): bool
    {
        return true;
    }

    public function supports(string|\Pinoox\Component\Package\Reference\ReferenceInterface $packageName): bool
    {
        return true;
    }

    public function path(string|\Pinoox\Component\Package\Reference\ReferenceInterface $packageName, string $path = ''): string
    {
        return '';
    }
}
