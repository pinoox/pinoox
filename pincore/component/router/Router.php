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
use Closure;
use pinoox\component\Http\RedirectResponse;
use pinoox\component\kernel\url\UrlGenerator;
use pinoox\component\package\AppManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class Router
{
    /**
     * current collection index
     * @var int
     */
    private int $current = -1;

    /**
     * @var Collection[]
     */
    private array $collections = [];

    /**
     * @var array
     */
    private array $actions = [];

    private AppManager $app;
    private UrlGeneratorInterface $urlGenerator;

    private const FOR_ROUTE = 'route';

    /**
     * all route names
     *
     * @var array
     */
    public array $names = [];

    public function __construct(AppManager $app)
    {
        $this->changeApp($app);
    }

    private function changeApp(AppManager $app): void
    {
        $this->app = $app;
    }

    public function path(string $name, array $params = []): string
    {
        if (empty($this->urlGenerator))
            $this->urlGenerator = new UrlGenerator($this->getMainCollection()->routes, RequestContext::fromUri(''));

        return $this->urlGenerator->generate($name, $params);
    }

    public function getAllPath(): array
    {
        $paths = [];
        $routes = $this->getMainCollection()->routes->all();
        /**
         * @var RouteCapsule $route
         */
        foreach ($routes as $name => $route) {
            $paths[$name] = $route->getPath();
        }

        return $paths;
    }

    /**
     * add route
     *
     * @param string|array $path
     * @param array|string|Closure $action
     * @param string $name
     * @param string|array $methods
     * @param array $defaults
     * @param array $filters
     */
    public function add(string|array $path, array|string|Closure $action = '', string $name = '', string|array $methods = [], array $defaults = [], array $filters = []): void
    {
        if (is_array($path)) {
            foreach ($path as $routeName => $p) {
                $routeName = is_string($routeName) ? $name . $routeName : $name . $this->generateName($this->currentCollection());
                $path = isset($p['path']) ? $p['path'] : $p;
                $action = isset($p['action']) ? $p['action'] : $action;
                $methods = isset($p['methods']) ? $p['methods'] : $methods;
                $defaults = isset($p['defaults']) ? $p['defaults'] : $defaults;
                $filters = isset($p['filters']) ? $p['filters'] : $filters;
                $this->add($path, $action, $routeName, $methods, $defaults, $filters);
            }
        } else {
            $name = $this->buildName($name);
            $route = $this->names[$name] = new Route(
                collection: $this->currentCollection(),
                path: $path,
                action: $action,
                name: $name,
                methods: $methods,
                defaults: $defaults,
                filters: $filters,
                priority: count($this->names),
            );

            $this->currentCollection()->add($route);
        }
    }

    /**
     * build action
     *
     * @param mixed $action
     * @param int|null $indexCollection
     * @return mixed
     */
    public function buildAction(mixed $action, ?int $indexCollection = null): mixed
    {
        $collection = isset($this->$collections[$indexCollection]) ? $this->collections[$indexCollection] : $this->currentCollection();
        return $collection->buildAction($action);
    }

    /**
     * get action
     *
     * @param string $name
     * @return mixed
     */
    public function getAction(string $name): mixed
    {
        $name = $this->buildNameAction($name, false);
        if (isset($this->actions[$name]))
            return $this->actions[$name];

        return false;
    }

    /**
     * add collection
     *
     * @param string $path
     * @param Router|string|array|callable|null $routes
     * @param mixed|null $controller
     * @param array|string $methods
     * @param array|string|Closure $action
     * @param array $defaults
     * @param array $filters
     * @param string $prefixName
     */
    public function collection(string $path = '', Router|string|array|callable|null $routes = null, mixed $controller = null, array|string $methods = [], array|string|Closure $action = '', array $defaults = [], array $filters = [], string $prefixName = ''): void
    {
        $cast = $this->current;
        $prefixName = $this->buildPrefixNameCollection($prefixName);
        $defaults = $this->buildDefaultsCollection($defaults);
        $filters = $this->buildFiltersCollection($filters);
        $controller = $this->buildControllerCollection($controller);
        $prefixPath = $this->buildPrefixPathCollection($path);

        $this->current = count($this->collections);
        $this->collections[$this->current] = new Collection(
            path: $path,
            prefixPath: $prefixPath,
            cast: $cast,
            controller: $controller,
            methods: $methods,
            action: $action,
            defaults: $defaults,
            filters: $filters,
            name: $prefixName,
        );

        $this->callRoutes($routes);

        $collection = $this->collections[$this->current];
        if ($collection->cast !== -1) {
            $this->current = $collection->cast;
            $this->collections[$this->current]->add($collection);
        }
    }

    /**
     * run a routes collection
     * @param Router|string|array|callable|null $routes
     */
    private function callRoutes(Router|string|array|callable|null $routes): void
    {
        if (empty($routes))
            return;

        if ($routes instanceof Router) {
            $this->getMainCollection()->add($routes->getMainCollection());
        } else if (is_callable($routes)) {
            $routes($this);
        } else {
            $this->loadFiles($routes);
        }
    }

    /**
     * load route file
     *
     * @param string|array $routes
     */
    private function loadFiles(string|array $routes): void
    {
        if (is_string($routes)) {
            $routes = !is_file($routes) ? $this->app->path($routes) : $routes;
            if (is_file($routes))
                include $routes;
        } else if (is_array($routes)) {
            foreach ($routes as $route) {
                $this->callRoutes($route);
            }
        }
    }

    /**
     * add action
     *
     * @param string $name
     * @param array|string|Closure $action
     */
    public function action(string $name, array|string|Closure $action): void
    {
        $name = $this->buildNameAction($name);
        $this->actions[$name] = $this->currentCollection()->buildAction($action);
    }

    /**
     * get current routes
     *
     * @return RouteCollection
     */
    private function currentRoutes(): RouteCollection
    {
        return $this->currentCollection()->routes;
    }

    /**
     * get current Collection
     *
     * @return Collection
     */
    public function currentCollection(): Collection
    {
        return $this->collections[$this->current];
    }

    public function getCollection($index = 0): ?Collection
    {
        return @$this->collections[$index];
    }

    /**
     * create name for action
     *
     * @param string $name
     * @param bool $isPrefix
     * @return string
     */
    private function buildNameAction(string $name, bool $isPrefix = true): string
    {
        $prefixName = $isPrefix ? $this->currentCollection()->name : '';
        return $prefixName . $name;
    }

    /**
     * build prefix name for collection
     *
     * @param $name
     * @return string
     */
    private function buildPrefixNameCollection($name): string
    {
        $prefix = $this->current > -1 ? $this->currentCollection()->name : '';
        return $prefix . $name;
    }

    /**
     * build prefix name for collection
     *
     * @param array $defaults
     * @return array
     */
    private function buildDefaultsCollection(array $defaults): array
    {
        if ($this->current > -1) {
            $defaults = array_merge($this->currentCollection()->defaults, $defaults);
        }
        return $defaults;
    }

    /**
     * build prefix name for collection
     *
     * @param array $filters
     * @return array
     */
    private function buildFiltersCollection(array $filters): array
    {
        if ($this->current > -1) {
            $filters = array_merge($this->currentCollection()->filters, $filters);
        }
        return $filters;
    }

    /**
     * build controller for collection
     *
     * @param mixed $controller
     * @return mixed
     */
    private function buildControllerCollection(mixed $controller): mixed
    {
        if ($this->current > -1) {
            $controller = !empty($controller) ? $controller : $this->currentCollection()->controller;
        }
        return $controller;
    }

    /**
     * build controller for collection
     *
     * @param string $path
     * @return mixed
     */
    private function buildPrefixPathCollection(string $path): string
    {
        $prefix = $this->current > -1 ? $this->currentCollection()->prefixPath : '';
        return $prefix . $path;
    }

    /**
     * get the main Collection
     *
     * @return Collection
     */
    public function getMainCollection(): Collection
    {
        return $this->collections[0];
    }

    /**
     * generate Name
     *
     * @param Collection|null $collection
     * @return string
     */
    public function generateName(?Collection $collection = null): string
    {
        $collection = !empty($collection) ? $collection : Router::currentCollection();
        $prefix = $collection->name;
        return $this->generateRandomName($prefix);
    }

    /**
     * build name for route
     *
     * @param string $name
     * @return string
     */
    private function buildName(string $name): string
    {
        $prefix = $this->currentCollection()->name;
        $name = !empty($name) ? $name : self::generateRandomName($prefix);
        return $prefix . $name;
    }

    /**
     * get all names
     *
     * @return array
     */
    public function all(): array
    {
        return $this->names;
    }

    /**
     * generate random name
     *
     * @param string $prefix
     * @return string
     */
    public function generateRandomName(string $prefix = ''): string
    {
        $name = self::FOR_ROUTE . '_' . count($this->names);
        if (isset($this->names[$prefix . $name])) {
            $name = self::FOR_ROUTE . '_' . Str::generateLowRandom(8);
        }
        return $name;
    }
}