<?php

use Pinoox\Component\Package\App as PackageApp;
use Pinoox\Component\Router\Action\ActionInvoker;
use Pinoox\Component\Router\Action\ActionReference;
use Pinoox\Component\Router\Action\ActionRegistry;
use Pinoox\Component\Router\Action\ActionValidationException;
use Pinoox\Component\Router\Action\ActionValidator;
use Pinoox\Component\Router\RouteName;
use Pinoox\Component\Router\Router as RouterComponent;
use Pinoox\Component\Router\RouteSourceRegistry;

it('registers and resolves named actions with route references', function () {
    $router = actionSystemRouter();

    $router->action('home', fn () => 'home-response');
    $router->route('/welcome', '@home')->get()->name('demo.home')->register();
    $router->syncActionRegistry();

    expect($router->resolveAction('@home'))->toBeCallable()
        ->and($router->findRouteByActionReference('@home'))->toBe('demo.home')
        ->and(ActionRegistry::get('com_test_actions', 'home'))->not->toBeNull()
        ->and(ActionRegistry::get('com_test_actions', 'home')->isUsed())->toBeTrue();
});

it('supports fluent action definition with metadata', function () {
    $router = actionSystemRouter();

    $router->action('pinooxjs')
        ->handle(fn () => 'js')
        ->description('Serve pinoox.js')
        ->flow('public')
        ->tag('asset')
        ->register();

    $router->action('view.home', fn () => 'view-home');

    $router->route('/js', '@pinooxjs')->get()->name('demo.js')->register();
    $router->route('/home', '@view.home')->get()->name('demo.view.home')->register();
    $router->syncActionRegistry();

    expect($router->actionFlows('pinooxjs'))->toBe(['public'])
        ->and($router->resolveAction('@view.home'))->toBeCallable()
        ->and(ActionRegistry::get('com_test_actions', 'view.home'))->not->toBeNull();
});

it('validates missing action references', function () {
    $router = actionSystemRouter();
    $router->route('/broken', '@missing')->get()->name('demo.missing')->register();

    $errors = (new ActionValidator())->validate($router, 'com_test_actions', false);

    expect($errors)->not->toBeEmpty()
        ->and($errors[0])->toContain('@missing');
});

it('throws when validating missing references in debug mode helper', function () {
    $router = actionSystemRouter();
    $router->route('/broken', '@missing')->get()->name('demo.missing')->register();

    expect(fn () => (new ActionValidator())->assertValid($router, 'com_test_actions', false))
        ->toThrow(ActionValidationException::class);
});

it('invokes a named action for tests', function () {
    $router = actionSystemRouter();
    $router->action('hello', fn () => 'hello-world');

    $response = ActionInvoker::invoke($router, '@hello');

    expect($response)->toBe('hello-world');
});

it('resolves scoped action references with ampersand prefix', function () {
    $router = actionSystemRouter();

    $router->collection(
        path: '/admin',
        routes: function (RouterComponent $router) {
            $router->action('dashboard', fn () => 'admin-dashboard');
            $router->route('/panel', '&dashboard')->get()->name('admin.dashboard')->register();
        },
        prefixName: 'admin.',
    );

    $router->syncActionRegistry();

    expect(ActionReference::resolveKey('&dashboard', 'admin.', array_keys($router->actions)))->toBe('admin.dashboard')
        ->and($router->resolveAction('&dashboard', 'admin.'))->toBeCallable();
});

it('finds route url by action reference through router helper', function () {
    $router = actionSystemRouter();
    $router->action('page', fn () => 'ok');
    $router->route('/pages/{id}', '@page')->get()->name('demo.page')->filters(['id' => '\d+'])->register();
    $router->syncActionRegistry();

    expect($router->path('demo.page', ['id' => 5]))->toBe('/pages/5')
        ->and($router->findRouteByActionReference('@page'))->toBe('demo.page');
});

it('exports serializable controller handler refs for action manifest cache', function () {
    $router = actionSystemRouter();
    $router->action('home', [ActionSystemController::class, 'home']);
    $router->syncActionRegistry();

    $manifest = ActionRegistry::exportManifest('com_test_actions');

    expect($manifest[0]['handler'])->toBe(ActionSystemController::class . '::home')
        ->and($manifest[0]['handler_ref'])->toBe([
            'type' => 'controller',
            'class' => ActionSystemController::class,
            'method' => 'home',
        ])
        ->and($manifest[0]['cacheable'])->toBeTrue();
});

it('marks closure handlers as non-cacheable in action manifest', function () {
    $router = actionSystemRouter();
    $router->action('inline', fn () => 'inline');
    $router->syncActionRegistry();

    $manifest = ActionRegistry::exportManifest('com_test_actions');

    expect($manifest[0]['handler'])->toBe('{closure}')
        ->and($manifest[0]['handler_ref'])->toBeNull()
        ->and($manifest[0]['cacheable'])->toBeFalse();
});

it('rehydrates controller handlers from cached manifest entries', function () {
    ActionRegistry::importManifest('com_test_actions', [[
        'name' => 'home',
        'handler' => ActionSystemController::class . '::home',
        'handler_ref' => [
            'type' => 'controller',
            'class' => ActionSystemController::class,
            'method' => 'home',
        ],
        'cacheable' => true,
        'description' => '',
        'flows' => [],
        'tags' => [],
        'routes' => ['demo.home'],
    ]]);

    $definition = ActionRegistry::get('com_test_actions', 'home');

    expect($definition)->not->toBeNull()
        ->and($definition->handler)->toBe([ActionSystemController::class, 'home'])
        ->and($definition->isCacheable())->toBeTrue();
});

it('records action source from route file instead of portal proxy', function () {
    RouteSourceRegistry::reset();

    $root = str_replace('\\', '/', dirname(__DIR__, 2));
    $actionsFile = $root . '/apps/com_pinoox_installer/routes/actions.php';

    RouteSourceRegistry::pushLoadingFile($actionsFile);
    RouteSourceRegistry::rememberAction(
        'home',
        [ActionSystemController::class, 'home'],
        [
            ['file' => $actionsFile, 'line' => 6, 'function' => 'action'],
            ['file' => $root . '/pincore/functions/router.php', 'line' => 57, 'function' => 'Pinoox\\Router\\action'],
            ['file' => $root . '/pincore/Component/Source/Portal.php', 'line' => 227, 'function' => 'callMethod'],
        ],
    );
    RouteSourceRegistry::popLoadingFile();

    $source = RouteSourceRegistry::action('home');

    expect($source['relative_file'])->toBe('apps/com_pinoox_installer/routes/actions.php')
        ->and($source['line'])->toBe(6)
        ->and($source['file'])->not->toContain('Portal.php');
});

class ActionSystemController
{
    public function home(): string
    {
        return 'home';
    }
}

afterEach(function () {
    ActionRegistry::reset();
    RouteSourceRegistry::reset();
});

function actionSystemRouter(string $package = 'com_test_actions'): RouterComponent
{
    $app = test()->createMock(PackageApp::class);
    $app->method('package')->willReturn($package);
    $app->method('path')->willReturnCallback(
        static fn (string $path = '') => rtrim(sys_get_temp_dir(), '/\\') . '/pinoox_action_tests/' . ltrim($path, '/\\')
    );

    return new RouterComponent(new RouteName(), $app);
}

