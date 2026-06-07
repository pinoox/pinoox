<?php

use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Package\App;
use Pinoox\Component\Package\AppRouter;
use Pinoox\Component\Package\Engine\EngineInterface;
use Pinoox\Component\Package\Parser\NameParser;
use Pinoox\Component\Package\Reference\ReferenceInterface;
use Pinoox\Component\Path\Path;
use Pinoox\Component\Path\Url;
use Pinoox\Component\Router\Router;
use Pinoox\Component\Store\Config\ConfigInterface;

it('resolves core path aliases through Path helpers', function () {
    $basePath = pathUrlTestNormalize(dirname(__DIR__, 2));
    $path = new Path($basePath, new NameParser(), new PathUrlTestEngine(), null);

    expect($path->root())->toBe($basePath)
        ->and($path->system('config/pinoox.config.php'))
        ->toBe($basePath . '/system/config/pinoox.config.php')
        ->and($path->apps('com_acme_demo'))
        ->toBe($basePath . '/apps/com_acme_demo')
        ->and($path->resolve('~config/app/source.config.php'))
        ->toBe($basePath . '/pincore/config/app/source.config.php');
});

it('builds scoped urls from an auto-detected request context', function () {
    $request = pathUrlTestRequest('http://localhost/manager/dashboard');

    $url = pathUrlTestMakeUrl($request, [
        '/manager' => 'com_pinoox_manager',
    ], 'com_pinoox_manager', '/manager');

    expect($url->origin())->toBe('http://localhost')
        ->and($url->base())->toBe('')
        ->and($url->forApp())->toBe('http://localhost/manager')
        ->and($url->to('profile'))->toBe('http://localhost/manager/profile')
        ->and($url->to('api/v1/users', Url::SCOPE_RELATIVE))->toBe('/api/v1/users')
        ->and($url->to('uploads/file.txt', Url::SCOPE_SITE))->toBe('http://localhost/uploads/file.txt')
        ->and($url->asset('resources/avatar.png'))->toBe('http://localhost/manager/resources/avatar.png')
        ->and($url->isSecure())->toBeFalse();
});

it('resolves app urls from the router map', function () {
    $request = pathUrlTestRequest('http://localhost/');
    $routes = [
        '/manager' => 'com_pinoox_manager',
        '/welcome' => 'com_pinoox_welcome',
    ];

    $url = pathUrlTestMakeUrl($request, $routes, 'com_pinoox_manager', '/manager');

    expect($url->appUrls('com_pinoox_manager'))
        ->toBe(['http://localhost/manager'])
        ->and($url->appUrl('com_pinoox_manager'))
        ->toBe('http://localhost/manager')
        ->and($url->forApp('com_pinoox_welcome'))
        ->toBe('http://localhost/welcome');
});

it('builds smart links with clean mode regardless of rewrite fallback', function () {
    $request = pathUrlTestRequest('http://localhost/pinoox/');

    $url = pathUrlTestMakeUrl($request, [
        '/pinoox' => 'com_pinoox_installer',
    ], 'com_pinoox_installer', '/pinoox');

    expect($url->isRoutePath('api/v1/ping'))->toBeTrue()
        ->and($url->isRoutePath('?_pnx=/api/v1/ping'))->toBeFalse()
        ->and($url->link('api/v1/ping', Url::SCOPE_APP, Url::MODE_CLEAN))
        ->toBe('http://localhost/pinoox/api/v1/ping')
        ->and($url->link('api/v1/ping', Url::SCOPE_APP, Url::MODE_AUTO))
        ->toContain('?_pnx=');
});

it('maps filesystem references under apps to public app urls', function () {
    $basePath = pathUrlTestNormalize(dirname(__DIR__, 2));
    $request = pathUrlTestRequest('http://localhost/manager');

    $url = pathUrlTestMakeUrl($request, [
        '/manager' => 'com_pinoox_manager',
    ], 'com_pinoox_manager', '/manager', $basePath);

    $reference = '~apps/com_pinoox_manager/resources/avatar.png';

    expect($url->reference($reference))
        ->toBe('http://localhost/manager/resources/avatar.png')
        ->and($url->fromPath($basePath . '/apps/com_pinoox_manager/icon.png'))
        ->toBe('http://localhost/apps/com_pinoox_manager/icon.png');
});

function pathUrlTestRequest(string $uri): Request
{
    $request = Request::create($uri, 'GET');
    $request->server->set('HTTP_HOST', parse_url($uri, PHP_URL_HOST) ?: 'localhost');

    return $request;
}

function pathUrlTestNormalize(string $path): string
{
    return rtrim(str_replace('\\', '/', $path), '/');
}

function pathUrlTestMakeUrl(
    Request $request,
    array $routes,
    ?string $package = 'com_pinoox_welcome',
    string $routePath = '/',
    ?string $basePath = null,
): Url {
    $basePath ??= pathUrlTestNormalize(dirname(__DIR__, 2));
    $engine = new PathUrlTestEngine($basePath);
    $appRouter = new AppRouter(new PathUrlTestConfig($routes), $engine, $request);
    $path = new Path($basePath, new NameParser(), $engine, $package);

    /** @var App $app */
    $app = test()->createMock(App::class);
    $app->method('package')->willReturn($package);
    $app->method('pathRoute')->willReturn($routePath);

    return new Url($app, $request, $appRouter, $path, $basePath);
}

class PathUrlTestConfig implements ConfigInterface
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

class PathUrlTestEngine implements EngineInterface
{
    public function __construct(private string $basePath = '')
    {
        if ($this->basePath === '') {
            $this->basePath = pathUrlTestNormalize(dirname(__DIR__, 2));
        }
    }

    public function config(string|ReferenceInterface $packageName): ConfigInterface
    {
        throw new RuntimeException('Not needed in this test.');
    }

    public function lang(string|ReferenceInterface $packageName): \Pinoox\Component\Translator\Translator
    {
        throw new RuntimeException('Not needed in this test.');
    }

    public function router(string|ReferenceInterface $packageName, string $path = ''): Router
    {
        throw new RuntimeException('Not needed in this test.');
    }

    public function exists(string|ReferenceInterface $packageName): bool
    {
        return is_string($packageName) && str_starts_with($packageName, 'com_');
    }

    public function stable(string|ReferenceInterface $packageName): bool
    {
        return true;
    }

    public function supports(string|ReferenceInterface $packageName): bool
    {
        return $this->exists($packageName);
    }

    public function path(string|ReferenceInterface $packageName, string $path = ''): string
    {
        $base = $this->basePath . '/apps/' . $packageName;

        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}
