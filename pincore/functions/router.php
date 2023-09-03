<?php


namespace pinoox\router;

use Closure;
use pinoox\portal\Router;

/**
 * add route
 *
 * @param array|string $path
 * @param array|string|Closure $action
 * @param string $name
 * @param string|array $methods
 * @param array $defaults
 * @param array $filters
 */
function route(array|string $path, array|string|Closure $action = '', string $name = '', string|array $methods = [], array $defaults = [], array $filters = []): void
{
    Router::add($path, $action, $name, $methods, $defaults, $filters);
}


/**
 * add collection
 * @param string $path
 * @param mixed|null $controller
 * @param array|string $methods
 * @param array|string|Closure $action
 * @param string|array|callable|Router|null $routes
 * @param array $filters
 * @param array $defaults
 * @param string $prefixName
 */
function collection(string $path = '', string|array|callable|Router|null $routes = null, mixed $controller = null, array|string $methods = [], array|string|Closure $action = '', array $filters = [], array $defaults = [], string $prefixName = ''): void
{
    Router::collection($path, $routes,$controller, $methods, $action, $defaults, $filters, $prefixName);
}


/**
 * generate action
 *
 * @param string $name
 * @param array|string|Closure $action
 */
function action(string $name, array|string|Closure $action): void
{
    Router::action($name, $action);
}


/**
 * add get method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 */
function get(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = []): void
{
    Router::get($path, $action, $name, $defaults, $filters);
}


/**
 * add post method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 */
function post(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = []): void
{
    Router::post($path, $action, $name, $defaults, $filters);
}


/**
 * add put method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 */
function put(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = []): void
{
    Router::put($path, $action, $name, $defaults, $filters);
}


/**
 * add patch method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 */
function patch(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = []): void
{
    Router::patch($path, $action, $name, $defaults, $filters);
}


/**
 * add delete method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 */
function delete(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = []): void
{
    Router::delete($path, $action, $name, $defaults, $filters);
}


/**
 * add options method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 */
function options(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = []): void
{
    Router::options($path, $action, $name, $defaults, $filters);
}


/**
 * add options method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 */
function head(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = []): void
{
    Router::head($path, $action, $name, $defaults, $filters);
}


/**
 * add purge method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 */
function purge(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = []): void
{
    Router::purge($path, $action, $name, $defaults, $filters);
}

/**
 * add trace method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 */
function trace(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = []): void
{
    Router::trace($path, $action, $name, $defaults, $filters);
}

/**
 * add connect method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 */
function connect(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = []): void
{
    Router::connect($path, $action, $name, $defaults, $filters);
}