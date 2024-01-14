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

use Closure;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\RedirectResponse;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Url\UrlGenerator;
use Pinoox\Component\Package\App;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
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
    public array $collections = [];

    /**
     * @var array
     */
    public array $actions = [];

    /**
     * @var array
     */
    private array $data = [];

    private App $app;
    private UrlGeneratorInterface $urlGenerator;

    private UrlMatcherInterface $urlMatcher;

    private RouteName $routeName;

    public function __construct(RouteName $routeName, App $app, ?Collection $collection = null, bool $isDefault = true)
    {
        $this->routeName = $routeName;
        if (!empty($collection)) {
            $this->current = 0;
            $this->collections[0] = $collection;
        } else {
            $this->collection();
        }
        $this->changeApp($app);

        if ($isDefault)
            $this->defaultRoutes();
    }

    private function changeApp(App $app): void
    {
        $this->app = $app;
    }

    public function getUrlGenerator(?RequestContext $context = null): UrlGeneratorInterface
    {
        if (empty($this->urlGenerator)) {
            $context = !empty($context) ? $context : RequestContext::fromUri('');
            $this->urlGenerator = new UrlGenerator($this->getCollection()->routes, $context);
        }

        return $this->urlGenerator;
    }

    public function path(string $name, array $params = [], ?Request $request = null): string
    {
        return $this->getUrlGenerator(
            $request?->getContext()
        )->generate($name, $params);
    }

    public function getUrlMatcher(?RequestContext $context = null): UrlMatcherInterface|RequestMatcherInterface
    {
        if (empty($this->urlMatcher)) {
            $context = !empty($context) ? $context : RequestContext::fromUri('');
            $this->urlMatcher = new UrlMatcher($this->getCollection()->routes, $context);
        }

        return $this->urlMatcher;
    }

    public function match(string $path, ?Request $request = null): array
    {
        return $this->getUrlMatcher(
            $request?->getContext()
        )->match($path);
    }

    public function matchRequest(Request $request): array
    {
        return $this->getUrlMatcher(
            $request->getContext()
        )->matchRequest($request);
    }

    public function getAllPath(): array
    {
        $paths = [];
        $routes = $this->getCollection()->routes->all();
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
    public function add(string|array $path, array|string|Closure $action = '', string $name = '', string|array $methods = [], array $defaults = [], array $filters = [], array $data = [],
    ): void
    {
        if (is_array($path)) {
            foreach ($path as $routeName => $p) {
                $routeName = is_string($routeName) ? $name . $routeName : $name . $this->routeName->generate($this->currentCollection()->name);
                $path = isset($p['path']) ? $p['path'] : $p;
                $action = isset($p['action']) ? $p['action'] : $action;
                $methods = isset($p['methods']) ? $p['methods'] : $methods;
                $defaults = isset($p['defaults']) ? $p['defaults'] : $defaults;
                $filters = isset($p['filters']) ? $p['filters'] : $filters;
                $data = isset($p['data']) ? $p['data'] : $data;
                $this->add($path, $action, $routeName, $methods, $defaults, $filters, $data);
            }
        } else {
            $name = $this->buildName($name);
            $route = new Route(
                collection: $this->currentCollection(),
                path: $path,
                action: $action,
                name: $name,
                methods: $methods,
                defaults: $defaults,
                filters: $filters,
                priority: $this->count(),
                data: $this->buildData($data),
            );

            $this->currentCollection()->add($route);
        }
    }

    private function buildData(array $data = []): array
    {
        return !empty($data) ? $data : $this->data;
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
     * @param array $data
     * @return Collection
     */
    public function collection(string $path = '', Router|string|array|callable|null $routes = null, mixed $controller = null, array|string $methods = [], array|string|Closure $action = '', array $defaults = [], array $filters = [], string $prefixName = '', array $data = []): Collection
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
            data: $this->buildData($data),
        );

        $this->callRoutes($routes);

        $collection = $this->collections[$this->current];
        if ($collection->cast !== -1) {
            $this->current = $collection->cast;
            $this->collections[$this->current]->add($collection);
        }

        return $collection;
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
            $this->getCollection()->add($routes->getCollection());
        } else if (is_callable($routes)) {
            $routes($this);
        } else {
            $this->loadFiles($routes);
        }
    }

    public function canonicalizePath(string $path): string
    {
        $path = array_filter(explode('/', $path));
        $path = implode('/', $path);
        return !empty($path) ? '/' . $path : '/';
    }

    public function build($path, $routes, array $data = []): Router
    {
        $this->data = $data;

        $collection = $this->collection(
            path: $path,
            routes: $routes
        );

        $collection->cast = -1;
        $this->data = [];
        $router = new Router($this->routeName, $this->app, $collection);
        $router->actions = $this->actions;
        return $router;
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
     * build name for route
     *
     * @param string $name
     * @return string
     */
    private function buildName(string $name = ''): string
    {
        $prefix = $this->currentCollection()->name;
        return $this->routeName->generate($prefix, $name);
    }

    /**
     * get all names
     *
     * @return array
     */
    public function all(): array
    {
        return $this->getCollection()->routes->all();
    }

    /**
     * get all names
     *
     * @return int
     */
    public function count(): int
    {
        return $this->getCollection()->routes->count();
    }

    private function defaultRoutes(): void
    {
        $this->add(
            path: ['/{slash_remover}/'],
            action: function (Route $route, $slash_remover = '') {
                $base = Str::lastDelete($route->getCollection()->path, '/');
                $slug = $base . '/' . $slash_remover;
                $slug = Str::lastDelete($slug, '/');
                return new RedirectResponse('~' . $slug, 301);
            },
            filters: [
                'slash_remover' => '.+/'
            ]
        );
    }
}