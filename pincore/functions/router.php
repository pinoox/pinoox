<?php


namespace Pinoox\Router;

use Closure;
use Pinoox\Portal\Router;

/**
 * add route
 *
 * @param array|string $path
 * @param array|string|Closure $action
 * @param string $name
 * @param string|array $methods
 * @param array $defaults
 * @param array $filters
 * @param int|null $property
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function route(array|string $path, array|string|Closure $action = '', string $name = '', string|array $methods = [], array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
{
    Router::add($path, $action, $name, $methods, $defaults, $filters, $property, $data, $flows,$tags);
}


/**
 * add collection
 * @param string $path
 * @param string|array|callable|Router|null $routes
 * @param mixed|null $controller
 * @param array|string $methods
 * @param array|string|Closure $action
 * @param array $filters
 * @param array $defaults
 * @param string $prefixName
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function collection(string $path = '', string|array|callable|Router|null $routes = null, mixed $controller = null, array|string $methods = [], array|string|Closure $action = '', array $filters = [], array $defaults = [], string $prefixName = '', array $data = [], array $flows = [], array $tags = []): void
{
    Router::collection($path, $routes, $controller, $methods, $action, $defaults, $filters, $prefixName, $data, $flows,$tags);
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
 * @param int|null $property
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function get(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
{
    Router::get($path, $action, $name, $defaults, $filters, $property, $data, $flows,$tags);
}


/**
 * add post method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 * @param int|null $property
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function post(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
{
    Router::post($path, $action, $name, $defaults, $filters, $property, $data, $flows,$tags);
}


/**
 * add put method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 * @param int|null $property
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function put(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
{
    Router::put($path, $action, $name, $defaults, $filters, $property, $data, $flows,$tags);
}


/**
 * add patch method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 * @param int|null $property
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function patch(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
{
    Router::patch($path, $action, $name, $defaults, $filters, $property, $data, $flows,$tags);
}


/**
 * add delete method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 * @param int|null $property
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function delete(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
{
    Router::delete($path, $action, $name, $defaults, $filters, $property, $data, $flows,$tags);
}


/**
 * add options method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 * @param int|null $property
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function options(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
{
    Router::options($path, $action, $name, $defaults, $filters, $property, $data, $flows,$tags);
}


/**
 * add options method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 * @param int|null $property
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function head(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
{
    Router::head($path, $action, $name, $defaults, $filters, $property, $data, $flows,$tags);
}


/**
 * add purge method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 * @param int|null $property
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function purge(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
{
    Router::purge($path, $action, $name, $defaults, $filters, $property, $data, $flows,$tags);
}

/**
 * add trace method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 * @param int|null $property
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function trace(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
{
    Router::trace($path, $action, $name, $defaults, $filters, $property, $data, $flows,$tags);
}

/**
 * add connect method route
 *
 * @param array|string $path
 * @param mixed|null $action
 * @param string $name
 * @param array $defaults
 * @param array $filters
 * @param int|null $property
 * @param array $data
 * @param array $flows
 * @param array $tags
 */
function connect(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
{
    Router::connect($path, $action, $name, $defaults, $filters, $property, $data, $flows,$tags);
}