<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\component\router;

use pinoox\component\Helpers\Str;
use pinoox\portal\app\App;

class Collection
{
    public RouteCollection $routes;

    public function __construct(
        public string       $path = '',
        public string       $prefixPath = '',
        public int          $cast = -1,
        public mixed        $controller = null,
        public string|array $methods = '',
        public mixed        $action = null,
        public array        $defaults = [],
        public array        $filters = [],
        public string       $name = '',
    )
    {

        $this->controller = $this->buildController($controller);
        if (is_string($methods) && !empty($methods)) {
            $methods = Str::multiExplode(['|', ',', '-'], $methods);
        }
        $this->methods = is_array($methods) ? $methods : [];
        $this->routes = new RouteCollection();
    }

    public function get(): RouteCollection
    {
        return $this->routes;
    }


    public function buildMethods($methods): array
    {
        if (is_string($methods)) {
            $methods = Str::multiExplode(['|', ',', '-'], $methods);
        }

        $methods = is_array($methods) ? array_filter($methods) : [];
        $methods = !empty($methods) ? $methods : $this->methods;
        return $methods;
    }

    public function buildController($controller)
    {
        if (is_string($controller) && !class_exists($controller) && !Str::firstHas($controller, 'pinoox')) {
            $controller = 'pinoox\\app\\' . App::package() . '\\controller\\' . $controller;
        }

        return $controller;
    }

    public function addRoute(Route $route)
    {
        $this->routes->add($route->getName(), $route->get(), $route->countAll());
    }

    public function buildAction($action)
    {
        if (is_string($action) || is_array($action)) {
            if (is_string($action))
                $parts = Str::multiExplode(['@', '::', ':'], $action);
            else
                $parts = $action;

            $countParts = count($parts);
            if ($countParts == 1) {
                $method = $parts[0];
                if (is_callable($method)) {
                    return $method;
                } else if (!empty($this->controller)) {
                    $class = $this->controller;
                    return [$class, $parts[0]];
                }
                else
                {
                    return $this->buildController($method);
                }
            } else if ($countParts == 2) {
                $class = $this->buildController($parts[0]);
                $method = $parts[1];
                return [$class, $method];
            }
        }

        return $action;
    }

    public function add(Route|Collection $input)
    {
        if ($input instanceof Route) {
            $this->addRoute($input);
        } else {
            $this->addCollection($input);
        }
    }

    public function addCollection(Collection $collection)
    {
//        if (!empty($collection->path))
//            $collection->routes->addPrefix($collection->path);

        $this->routes->addCollection($collection->routes);
    }

}