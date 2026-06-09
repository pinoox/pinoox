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
    $basePath = pathUrlTestNormalize(testProjectRoot());
    $path = new Path($basePath, new NameParser(), new PathUrlTestEngine(), null);

    expect($path->root())->toBe($basePath)
        ->and($path->system('pinoox.config.php'))
        ->toBe($basePath . '/pincore/config/pinoox.config.php')
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
        ->and($url->asset('resources/avatar.png'))->toBe('http://localhost/apps/com_pinoox_manager/resources/avatar.png')
        ->and($url->isSecure())->toBeFalse();
});

it('resolves structured url context parts for root and subfolder installs', function () {
    $rootRequest = pathUrlTestRequest('http://domain.com/manager/user/');
    $rootUrl = pathUrlTestMakeUrl($rootRequest, [
        '/manager' => 'com_pinoox_manager',
    ], 'com_pinoox_manager', '/manager');

    expect($rootUrl->accessor()->toArray())->toBe([
        'domain' => 'domain.com',
        'site' => 'http://domain.com',
        'app' => 'http://domain.com/manager',
        'path' => '/',
        'appPath' => '/manager',
        'routeSegment' => 'manager',
        'api' => 'http://domain.com/manager/api/v1/',
        'apiPath' => '/manager/api/v1/',
        'resource' => 'http://domain.com/apps/com_pinoox_manager',
        'resourcePath' => '/apps/com_pinoox_manager',
        'theme' => 'http://domain.com/apps/com_pinoox_manager/theme/spark',
        'themePath' => '/apps/com_pinoox_manager/theme/spark',
        'resources' => 'http://domain.com/apps/com_pinoox_manager/resources/',
        'avatar' => 'http://domain.com/apps/com_pinoox_manager/resources/avatar.png',
        'appIcon' => 'http://domain.com/apps/com_pinoox_manager/resources/default.png',
    ])
        ->and($rootUrl->sitePath())->toBe('/')
        ->and($rootUrl->appPath())->toBe('/manager')
        ->and($rootUrl->asset('resources/avatar.png'))->toBe('http://domain.com/apps/com_pinoox_manager/resources/avatar.png')
        ->and($rootUrl->asset('apps/com_pinoox_manager/resources/avatar.png'))->toBe('http://domain.com/apps/com_pinoox_manager/resources/avatar.png')
        ->and($rootUrl->accessor()->resource('resources/'))->toBe('http://domain.com/apps/com_pinoox_manager/resources/')
        ->and($rootUrl->accessor()->resourcePath('resources/'))->toBe('/apps/com_pinoox_manager/resources/')
        ->and($rootUrl->accessor()->api('auth/login'))->toBe('http://domain.com/manager/api/v1/auth/login')
        ->and($rootUrl->to('', Url::SCOPE_APP_PATH))->toBe('/manager');

    $subRequest = pathUrlTestRequest('http://domain.com/pinoox/manager/user/');
    $subRequest->server->set('SCRIPT_NAME', '/pinoox/index.php');
    $subRequest->server->set('SCRIPT_FILENAME', '/var/www/pinoox/index.php');

    $subUrl = pathUrlTestMakeUrl($subRequest, [
        '/manager' => 'com_pinoox_manager',
    ], 'com_pinoox_manager', '/manager');

    expect($subUrl->accessor()->toArray())->toBe([
        'domain' => 'domain.com',
        'site' => 'http://domain.com/pinoox',
        'app' => 'http://domain.com/pinoox/manager',
        'path' => '/pinoox',
        'appPath' => '/pinoox/manager',
        'routeSegment' => 'manager',
        'api' => 'http://domain.com/pinoox/manager/api/v1/',
        'apiPath' => '/pinoox/manager/api/v1/',
        'resource' => 'http://domain.com/pinoox/apps/com_pinoox_manager',
        'resourcePath' => '/pinoox/apps/com_pinoox_manager',
        'theme' => 'http://domain.com/pinoox/apps/com_pinoox_manager/theme/spark',
        'themePath' => '/pinoox/apps/com_pinoox_manager/theme/spark',
        'resources' => 'http://domain.com/pinoox/apps/com_pinoox_manager/resources/',
        'avatar' => 'http://domain.com/pinoox/apps/com_pinoox_manager/resources/avatar.png',
        'appIcon' => 'http://domain.com/pinoox/apps/com_pinoox_manager/resources/default.png',
    ])
        ->and($subUrl->to('', Url::SCOPE_APP_PATH))->toBe('/pinoox/manager')
        ->and($subUrl->accessor()->resource('resources/avatar.png'))
        ->toBe('http://domain.com/pinoox/apps/com_pinoox_manager/resources/avatar.png');
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
        ->toBe('http://localhost/pinoox/api/v1/ping');

    $autoLink = $url->link('api/v1/ping', Url::SCOPE_APP, Url::MODE_AUTO);

    if (\Pinoox\Component\Router\QueryRouteResolver::rewriteAppearsActive()) {
        expect($autoLink)->toBe('http://localhost/pinoox/api/v1/ping');
    } else {
        expect($autoLink)->toMatch('/\?(?:_pnx=|route=)/');
    }
});

it('maps filesystem references under apps to public app urls', function () {
    $basePath = pathUrlTestNormalize(testProjectRoot());
    $request = pathUrlTestRequest('http://localhost/manager');

    $url = pathUrlTestMakeUrl($request, [
        '/manager' => 'com_pinoox_manager',
    ], 'com_pinoox_manager', '/manager', $basePath);

    $reference = '~apps/com_pinoox_manager/resources/avatar.png';

    expect($url->reference($reference))
        ->toBe('http://localhost/apps/com_pinoox_manager/resources/avatar.png')
        ->and($url->fromPath($basePath . '/apps/com_pinoox_manager/icon.png'))
        ->toBe('http://localhost/apps/com_pinoox_manager/icon.png');
});

it('builds theme asset urls through theme accessor', function () {
    $request = pathUrlTestRequest('http://localhost/pinoox/manager/dashboard');
    $request->server->set('SCRIPT_NAME', '/pinoox/index.php');

    $url = pathUrlTestMakeUrl($request, [
        '/manager' => 'com_pinoox_manager',
    ], 'com_pinoox_manager', '/manager');

    $expected = 'http://localhost/pinoox/apps/com_pinoox_manager/theme/spark/index.html';
    $base = 'http://localhost/pinoox/apps/com_pinoox_manager/theme/spark';
    $theme = $url->themeAccessor('spark');

    expect($theme->assets('index.html'))->toBe($expected)
        ->and($url->themeAccessor()->assets('index.html'))->toBe($expected)
        ->and($theme->name())->toBe('spark')
        ->and($theme->getName())->toBe('spark')
        ->and($theme->url())->toBe($base)
        ->and($theme->path())->toBe('/pinoox/apps/com_pinoox_manager/theme/spark')
        ->and($theme->config('api'))->toBeTrue()
        ->and($theme->title('en'))->toBe('Spark')
        ->and($theme->lang('description', 'en'))->not->toBe('')
        ->and($theme->root())->toContain('theme/spark')
        ->and(theme('spark', 'com_pinoox_manager'))->toBeInstanceOf(\Pinoox\Component\Path\ThemeAccessor::class);
});

it('reads app manifest through app accessor', function () {
    $request = pathUrlTestRequest('http://localhost/pinoox/manager/dashboard');
    $request->server->set('SCRIPT_NAME', '/pinoox/index.php');

    $url = pathUrlTestMakeUrl($request, [
        '/manager' => 'com_pinoox_manager',
    ], 'com_pinoox_manager', '/manager');

    $manifest = $url->appAccessor('com_pinoox_manager');
    $expectedLang = \Pinoox\Portal\App\AppEngine::config('com_pinoox_manager')->get('lang');

    expect($manifest->package())->toBe('com_pinoox_manager')
        ->and($manifest->name())->toBe('manager')
        ->and($manifest->config('lang'))->toBe($expectedLang)
        ->and($manifest->themeName())->toBe('spark')
        ->and($manifest->url())->toBe('http://localhost/pinoox/manager')
        ->and($manifest->path())->toBe('/pinoox/manager')
        ->and($url->accessor()->app())->toBe($manifest->url())
        ->and($url->accessor()->appPath())->toBe($manifest->path())
        ->and($manifest->root())->toContain('apps/com_pinoox_manager')
        ->and($manifest->resource('resources/avatar.png'))
        ->toBe('http://localhost/pinoox/apps/com_pinoox_manager/resources/avatar.png')
        ->and($manifest->theme()->name())->toBe('spark')
        ->and($manifest->versionName())->toBe('2.2.0')
        ->and(app('com_pinoox_manager')->name())->toBe('manager')
        ->and(app('com_pinoox_manager')->config('lang'))->toBe($expectedLang)
        ->and(app('com_pinoox_manager'))->toBeInstanceOf(\Pinoox\Component\Path\AppAccessor::class);
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
    $basePath ??= pathUrlTestNormalize(testProjectRoot());
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
            $this->basePath = pathUrlTestNormalize(testProjectRoot());
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

