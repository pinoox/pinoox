<?php

namespace Feature;

use PHPUnit\Framework\TestCase;
use Pinoox\Component\Kernel\Debug\Support\RouteContextResolver;
use Pinoox\Component\Router\Collection;
use Pinoox\Component\Router\Route;
use Pinoox\Component\Router\RouteSourceRegistry;
use Symfony\Component\HttpFoundation\Request;

class RouteContextResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        RouteSourceRegistry::reset();
    }

    public function test_resolves_named_action_source_for_route_reference(): void
    {
        $actionsFile = sys_get_temp_dir() . '/pinoox_test_actions_' . uniqid('', true) . '.php';
        file_put_contents($actionsFile, "<?php\naction('home', fn() => \\Pinoox\\Portal\\View::render3('index'));\n");

        RouteSourceRegistry::rememberAction(
            'home',
            '@home',
            [
                ['file' => $actionsFile, 'line' => 2, 'function' => 'action'],
            ],
            $actionsFile,
        );

        $routesFile = sys_get_temp_dir() . '/pinoox_test_routes_' . uniqid('', true) . '.php';
        file_put_contents($routesFile, '<?php' . "\n" . '$this->route(\'/\', \'@home\')->get()->name(\'demo.home\')->register();' . "\n");

        RouteSourceRegistry::rememberRoute(
            'demo.home',
            '@home',
            [
                ['file' => $routesFile, 'line' => 2, 'function' => 'register'],
            ],
        );

        $collection = new Collection(name: '');
        $route = new Route(
            collection: $collection,
            path: '/',
            action: '@home',
            name: 'demo.home',
            methods: ['GET'],
        );

        $request = Request::create('/', 'GET');
        $request->attributes->set('_router', $route);
        $request->attributes->set('_route', 'demo.home');
        $request->attributes->set('_controller', '@home');

        $context = RouteContextResolver::resolve($request);

        $actionSnippet = implode("\n", array_column($context['action_source']['snippet'], 'content'));

        $this->assertSame('demo.home', $context['name']);
        $this->assertSame('@home', $context['action_ref']);
        $this->assertStringContainsString('action(', $actionSnippet);
        $this->assertStringContainsString("render3('index')", $actionSnippet);

        @unlink($actionsFile);
        @unlink($routesFile);
    }

    public function test_resolves_inline_closure_route_definition(): void
    {
        $routesFile = sys_get_temp_dir() . '/pinoox_test_inline_' . uniqid('', true) . '.php';
        file_put_contents($routesFile, '<?php' . "\n" . '$this->route(\'*\', fn() => \Pinoox\Portal\View::render3(\'main\'))->get()->register();' . "\n");

        RouteSourceRegistry::rememberRoute(
            'demo.fallback',
            '{closure}',
            [
                ['file' => $routesFile, 'line' => 2, 'function' => 'register'],
            ],
            $routesFile,
        );

        $collection = new Collection(name: '');
        $route = new Route(
            collection: $collection,
            path: '*',
            action: fn () => null,
            name: 'demo.fallback',
            methods: ['GET'],
        );

        $request = Request::create('/missing', 'GET');
        $request->attributes->set('_router', $route);
        $request->attributes->set('_route', 'demo.fallback');

        $context = RouteContextResolver::resolve($request);

        $this->assertSame('{closure}', $context['action_ref']);
        $this->assertStringContainsString("render3('main')", implode("\n", array_column($context['route_source']['snippet'], 'content')));

        @unlink($routesFile);
    }
}

