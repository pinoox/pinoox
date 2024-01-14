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
 */
function route(array|string $path, array|string|Closure $action = '', string $name = '', string|array $methods = [], array $defaults = [], array $filters = [], ?int $property = null, array $data = []): void
{
    Router::add($path, $action, $name, $methods, $defaults, $filters,$property,$data);
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
 * @param int|null $property
 * @param array $data
 */
function get(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = []): void
{
    Router::get($path, $action, $name, $defaults, $filters,$property,$data);
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
 */
function post(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = []): void
{
    Router::post($path, $action, $name, $defaults, $filters,$property,$data);
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
 */
function put(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = []): void
{
    Router::put($path, $action, $name, $defaults, $filters,$property,$data);
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
 */
function patch(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = []): void
{
    Router::patch($path, $action, $name, $defaults, $filters,$property,$data);
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
 */
function delete(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = []): void
{
    Router::delete($path, $action, $name, $defaults, $filters,$property,$data);
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
 */
function options(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = []): void
{
    Router::options($path, $action, $name, $defaults, $filters,$property,$data);
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
 */
function head(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = []): void
{
    Router::head($path, $action, $name, $defaults, $filters,$property,$data);
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
 */
function purge(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = []): void
{
    Router::purge($path, $action, $name, $defaults, $filters,$property,$data);
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
 */
function trace(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = []): void
{
    Router::trace($path, $action, $name, $defaults, $filters,$property,$data);
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
 */
function connect(array|string $path, array|string|Closure $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = []): void
{
    Router::connect($path, $action, $name, $defaults, $filters,$property,$data);
}