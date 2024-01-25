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

namespace Pinoox\Component\Router;

use Pinoox\Component\Helpers\Str;
use Pinoox\Portal\App\App;

class Collection
{
    public RouteCollection $routes;
    private ControllerBuilder $controllerBuilder;

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
        public array        $data = [],
        public string       $prefixController = '',
        public array        $services = [],
    )
    {

        $this->controllerBuilder = new ControllerBuilder($controller, $this->prefixController);
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

    public function getData(): array
    {
        return $this->data;
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
        return $this->controllerBuilder->controller($controller);
    }

    public function addRoute(Route $route)
    {
        $this->routes->add($route->getName(), $route->get(), $route->getPriority());
    }

    public function buildAction($action)
    {
        return $this->controllerBuilder->action($action);
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