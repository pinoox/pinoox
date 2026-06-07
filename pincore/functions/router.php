<?php

namespace Pinoox\Router;

use Closure;
use Pinoox\Component\Router\RouteBuilder;
use Pinoox\Component\Router\RouteEntryBuilder;
use Pinoox\Portal\Route as RouteFacade;
use Pinoox\Portal\Router;

/**
 * Register a named action.
 *
 * action('home', fn () => ...);            // immediate
 * action('home')->handle(...)->register(); // with metadata
 */
function action(string $name, array|string|Closure|null $handler = null): ?\Pinoox\Component\Router\Action\ActionBuilder
{
    return Router::action($name, $handler);
}

function get(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
{
    return RouteFacade::get($path, $action);
}

function post(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
{
    return RouteFacade::post($path, $action);
}

function put(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
{
    return RouteFacade::put($path, $action);
}

function patch(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
{
    return RouteFacade::patch($path, $action);
}

function delete(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
{
    return RouteFacade::delete($path, $action);
}

function route_match(array|string $methods, string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
{
    return RouteFacade::match($methods, $path, $action);
}

function group(array $attributes, callable $callback): void
{
    RouteFacade::group($attributes, $callback);
}

/**
 * @return list<array<string, mixed>>
 */
function collect(callable $callback): array
{
    return RouteFacade::collect($callback);
}

/**
 * Resolve the fully-qualified route name for the active app or a package.
 *
 * route_name('home') => installer.home
 * route_name('home', 'com_pinoox_manager') => manager.home
 */
function route_name(string $name, ?string $package = null): string
{
    return \Pinoox\Component\Router\RouteNaming::full($name, $package);
}

/**
 * Generate a URL for a route name (short or fully-qualified).
 */
function route(string $name, array $parameters = [], bool $absolute = true): string
{
    return \Pinoox\Portal\Url::route(route_name($name, null), $parameters, $absolute);
}

/**
 * Route file helper — config manifest entry point.
 *
 * get('/', '@home')->name('home');
 * return routes([..., 'routes' => collect(fn () => ...)]);
 */
function routes(array|callable|null $definition = null): array|\Pinoox\Component\Router\RouteFile|null
{
    if (is_array($definition)) {
        return \Pinoox\Component\Router\RouteManifest::normalizeManifest($definition);
    }

    $router = null;

    try {
        $router = Router::___();
    } catch (\Throwable) {
        $router = null;
    }

    if ($definition === null) {
        return new \Pinoox\Component\Router\RouteFile($router);
    }

    if ($router === null) {
        throw new \RuntimeException('Route callback requires an active router context.');
    }

    (new \Pinoox\Component\Router\RouteFile($router))->register($definition);

    return null;
}

