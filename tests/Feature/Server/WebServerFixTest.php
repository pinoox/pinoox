<?php

use Pinoox\Component\Package\App as PackageApp;
use Pinoox\Component\Router\RouteName;
use Pinoox\Component\Router\Router as RouterComponent;
use Pinoox\Component\Server\FrontController;
use Pinoox\Component\Server\WebServerFix;
use Pinoox\Component\Server\WebServerFixCache;
use Tests\Support\TestSandbox;

afterEach(function () {
    WebServerFix::resetResolvedPaths();
});

it('auto-detects extension routes for web-server fix', function () {
    expect(WebServerFix::pathHasStaticExtension('/dist/pinoox.js'))->toBeTrue()
        ->and(WebServerFix::pathHasStaticExtension('/theme/test.css'))->toBeTrue()
        ->and(WebServerFix::pathHasStaticExtension('/api/{id}.json'))->toBeFalse()
        ->and(WebServerFix::pathHasStaticExtension('/assets/app.js'))->toBeTrue()
        ->and(WebServerFix::pathHasStaticExtension('/page/about'))->toBeFalse();
});

it('joins app router mount with app-relative fix paths', function () {
    expect(WebServerFix::joinMount('/manager', '/dist/pinoox.js'))->toBe('/manager/dist/pinoox.js')
        ->and(WebServerFix::joinMount('/', '/dist/pinoox.js'))->toBe('/dist/pinoox.js')
        ->and(WebServerFix::relativeToMount('/manager', '/manager/dist/pinoox.js'))->toBe('/dist/pinoox.js');
});

it('registers fix paths when routes with extensions are built', function () {
    $package = testPackage('webfix');
    $app = test()->createMock(PackageApp::class);
    $app->method('package')->willReturn($package);
    $app->method('path')->willReturnCallback(
        static fn (string $path = '') => TestSandbox::fakeAppPath('webfix', $path)
    );

    $router = new RouterComponent(new RouteName(), $app);
    $router->appMountPath = '/manager';
    $router->route('/dist/pinoox.js', fn () => 'js')->get()->name('pinooxjs')->register();
    $router->route('/theme/test.css', fn () => 'css')->get()->name('theme.css')->register();
    webServerFixFlushRouter($router, $package);

    $cached = WebServerFixCache::load($package);

    expect($cached)->toHaveCount(2)
        ->and($cached[0]['relative'] ?? null)->toBeIn(['/dist/pinoox.js', '/theme/test.css']);
});

it('detects front-controller paths from cache and extension fallback', function () {
    $package = testPackage('webfix_fallback');
    WebServerFixCache::merge($package, [
        ['relative' => '/dist/pinoox.js', 'name' => 'pinooxjs'],
    ]);

    $root = TestSandbox::documentRoot();
    TestSandbox::touch('docroot/assets/real.js', 'console.log(1);');

    expect(WebServerFix::matches('/manager/dist/pinoox.js', $root))->toBeFalse()
        ->and(FrontController::shouldRoute('/manager/dist/pinoox.js', $root))->toBeTrue()
        ->and(FrontController::shouldRoute('/dist/pinoox.js', $root))->toBeTrue()
        ->and(FrontController::shouldRoute('/assets/real.js', $root))->toBeFalse();
});

it('normalizes php built-in server globals for front-controller paths', function () {
    $_SERVER['SCRIPT_NAME'] = '/dist/pinoox.js';
    $_SERVER['PHP_SELF'] = '/dist/pinoox.js';
    $_SERVER['PATH_INFO'] = '';
    $_SERVER['REDIRECT_URL'] = '/dist/pinoox.js';

    FrontController::applyServerGlobals('/dist/pinoox.js');

    expect($_SERVER['SCRIPT_NAME'])->toBe('/index.php')
        ->and($_SERVER['PATH_INFO'])->toBe('/dist/pinoox.js')
        ->and(isset($_SERVER['REDIRECT_URL']))->toBeFalse();

    $_SERVER['SCRIPT_NAME'] = '/manager/dist/pinoox.js';
    $_SERVER['PHP_SELF'] = '/manager/dist/pinoox.js';

    FrontController::applyServerGlobals('/manager/dist/pinoox.js');

    expect($_SERVER['SCRIPT_NAME'])->toBe('/index.php')
        ->and($_SERVER['PATH_INFO'])->toBe('/manager/dist/pinoox.js');
});

it('development server router script always routes front-controller paths through index.php', function () {
    $routerScript = testProjectRoot() . '/launcher/server.php';
    $contents = file_get_contents($routerScript);

    expect($contents)
        ->toContain('FrontController::shouldRoute')
        ->toContain('FrontController::applyServerGlobals')
        ->toContain('require $documentRoot . \'/index.php\';');
});

it('registers and matches mounted /manager/dist/pinoox.js route path', function () {
    $package = testPackage('pinooxjs');
    $app = test()->createMock(PackageApp::class);
    $app->method('package')->willReturn($package);
    $app->method('path')->willReturnCallback(
        static fn (string $path = '') => TestSandbox::fakeAppPath('pinooxjs', $path)
    );

    $router = new RouterComponent(new RouteName(), $app);
    $router->collection('/manager', function () use ($router) {
        $router->route('/dist/pinoox.js', fn () => 'js')->get()->name('pinooxjs')->register();
    });

    expect($router->getAllPath()['pinooxjs'] ?? null)->toBe('/manager/dist/pinoox.js');

    $match = $router->match('/manager/dist/pinoox.js');

    expect($match['_route'] ?? null)->toBe('pinooxjs');
});

function webServerFixFlushRouter(RouterComponent $router, string $package): void
{
    $reflection = new ReflectionClass($router);
    $method = $reflection->getMethod('finalizeAfterBuild');
    $method->setAccessible(true);
    $method->invoke($router, null);
}
