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
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Url\UrlGenerator;
use Pinoox\Component\Package\App;
use Pinoox\Component\Path\Manager\PathManager;
use Pinoox\Component\Router\Action\ActionBuilder;
use Pinoox\Component\Router\Action\ActionCache;
use Pinoox\Component\Router\Action\ActionDefinition;
use Pinoox\Component\Router\Action\ActionReference;
use Pinoox\Component\Router\Action\ActionHandlerRef;
use Pinoox\Component\Router\Action\ActionRegistry;
use Pinoox\Component\Router\Action\ActionValidator;
use Pinoox\Component\Router\RouteSourceRegistry;
use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Component\Server\WebServerFix;
use Pinoox\Component\Server\WebServerFixRegistry;
use Pinoox\Portal\Mode;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Pinoox\Component\Router\UrlMatcher;
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
     * @var array<string, array{description?: string, flows?: list<string>, tags?: list<string>, group?: string|null}>
     */
    public array $actionMeta = [];

    /**
     * Reset router state when the portal container is rebuilt (tests / app switch).
     */
    public function __portalRebuild(): void
    {
        $this->actions = [];
        $this->actionMeta = [];
        $this->collections = [];
        $this->current = -1;
        $this->appMountPath = '/';
    }

    private App $app;
    private UrlGeneratorInterface $urlGenerator;

    private UrlMatcherInterface $urlMatcher;

    private RouteName $routeName;

    private array $routeFileStack = [];

    public string $appMountPath = '/';

    public function __construct(RouteName $routeName, App $app, ?Collection $collection = null, bool $isDefault = true)
    {
        $this->app = $app;
        $this->routeName = $routeName;

        if (!empty($collection)) {
            $this->current = 0;
            $this->collections[0] = $collection;
        } else {
            $this->collection();
        }
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

    public function controller(string $name)
    {
        return $this->currentRoutes()?->get($name)?->getDefault('_controller');
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
     * @param int|null $property
     * @param array $data
     * @param array $flows
     * @param array $tags
     */
    public function add(string|array $path, array|string|Closure $action = '', string $name = '', string|array $methods = [], array $defaults = [], array $filters = [], ?int $property = null, array $data = [], array $flows = [], array $tags = []): void
    {
        if (is_array($path)) {
            foreach ($path as $routeName => $p) {
                $routeName = is_string($routeName) ? $name . $routeName : $name . $this->routeName->generate($this->currentCollection()->name);
                $path = $p['path'] ?? $p;
                $action = $p['action'] ?? $action;
                $methods = $p['methods'] ?? $methods;
                $defaults = $p['defaults'] ?? $defaults;
                $filters = $p['filters'] ?? $filters;
                $data = $p['data'] ?? $data;
                $property = $p['property'] ?? $property;
                $flows = $p['flows'] ?? $flows;
                $tags = $p['tags'] ?? $tags;
                $this->add($path, $action, $routeName, $methods, $defaults, $filters, $property, $data, $flows, $tags);
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
                priority: is_null($property) ? $this->count() : $property,
                data: $data,
                flows: $flows,
                tags: $tags,
            );

            $this->currentCollection()->add($route);
            RouteSourceRegistry::rememberRoute(
                $route->getName(),
                $action,
                debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                end($this->routeFileStack) ?: null,
            );
            $this->trackActionRoute($route, $name, $action);
            $this->trackWebServerFixRoute($route, $name);
        }
    }

    private function trackWebServerFixRoute(Route $route, string $routeName): void
    {
        $package = (string) ($this->app->package() ?? '');

        if ($package === '') {
            return;
        }

        $routePath = $route->getPath();
        $data = $route->getData();
        $shouldFix = array_key_exists('fix_web_server', $data)
            ? (bool) $data['fix_web_server']
            : WebServerFix::pathHasStaticExtension($routePath);

        if (!$shouldFix) {
            return;
        }

        WebServerFixRegistry::register(
            $package,
            WebServerFix::relativeToMount($this->appMountPath, $routePath),
            $routeName,
            $routePath,
        );
    }

    private function trackActionRoute(Route $route, string $routeName, mixed $declaredAction): void
    {
        if (!is_string($declaredAction) || !ActionReference::isReference($declaredAction)) {
            return;
        }

        $package = (string) ($this->app->package() ?? '');
        if ($package === '') {
            return;
        }

        $collectionPrefix = $route->getCollection()->name;
        $resolved = ActionReference::resolveKey($declaredAction, $collectionPrefix, array_keys($this->actions));
        if ($resolved !== null) {
            ActionRegistry::linkRoute($package, $routeName, $resolved, $route->getPath(), $declaredAction);
        }
    }

    public function builder(): RouteBuilder
    {
        return new RouteBuilder($this);
    }

    public function route(string $path, array|string|Closure $action = '', string|array $methods = [], string $name = ''): RouteBuilder
    {
        return $this->builder()
            ->path($path)
            ->action($action)
            ->methods($methods)
            ->name($name);
    }

    /**
     * Define routes from a config manifest or builder callback.
     *
     * @param array<string, mixed>|callable(RouteRegister): void $definition
     * @return array<string, mixed>|null
     */
    public function routes(array|callable $definition): ?array
    {
        if (is_array($definition)) {
            $manifest = RouteManifest::normalizeManifest($definition);
            RouteManifest::apply($this, $manifest);

            return $manifest;
        }

        $definition(new RouteRegister($this));

        return null;
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
        $collection = isset($this->collections[$indexCollection]) ? $this->collections[$indexCollection] : $this->currentCollection();
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
        return $this->resolveAction($name);
    }

    public function resolveAction(string $name, ?string $collectionPrefix = null): mixed
    {
        $collectionPrefix ??= $this->currentCollection()->name;
        $resolvedKey = ActionReference::resolveKey(
            str_starts_with($name, '@') || str_starts_with($name, '&') ? $name : '@' . $name,
            $collectionPrefix,
            array_keys($this->actions),
        );

        if ($resolvedKey === null) {
            $resolvedKey = ActionReference::resolveKey('@' . ltrim($name, '@&'), $collectionPrefix, array_keys($this->actions));
        }

        if ($resolvedKey !== null && isset($this->actions[$resolvedKey])) {
            return $this->actions[$resolvedKey];
        }

        $direct = $this->buildNameAction($name, false);
        if (isset($this->actions[$direct])) {
            return $this->actions[$direct];
        }

        return false;
    }

    /** @return list<string> */

    public function actionFlows(string $actionKey): array
    {
        return $this->actionMeta[$actionKey]['flows'] ?? [];
    }

    /**
     * Register a named action or start a fluent definition.
     *
     * action('home', fn () => ...);           // register immediately
     * action('home')->handle(...)->register(); // fluent with metadata
     */
    public function action(string $name, array|string|Closure|null $handler = null): ?ActionBuilder
    {
        if ($handler !== null) {
            $this->registerNamedAction($name, $handler);

            return null;
        }

        return new ActionBuilder($this, $name);
    }

    /**
     * @param list<string> $flows
     * @param list<string> $tags
     */
    public function registerNamedAction(
        string $name,
        array|string|Closure $handler,
        string $description = '',
        array $flows = [],
        array $tags = [],
    ): string {
        $fullName = $this->buildNameAction($name);
        if (isset($this->actions[$fullName])) {
            throw new \InvalidArgumentException(sprintf('Action "%s" is already registered.', $fullName));
        }

        $group = str_contains($fullName, '.') ? strstr($fullName, '.', true) : null;

        $this->actions[$fullName] = $this->currentCollection()->buildAction($handler);
        $this->actionMeta[$fullName] = [
            'description' => $description,
            'flows' => array_values(array_unique($flows)),
            'tags' => array_values(array_unique($tags)),
            'group' => $group ?: null,
        ];

        RouteSourceRegistry::rememberAction(
            $fullName,
            $handler,
            debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 12),
            $this->currentRouteFile(),
        );

        $source = RouteSourceRegistry::action($fullName) ?? [];
        $package = (string) ($this->app->package() ?? '');

        if ($package !== '') {
            ActionRegistry::register($package, new ActionDefinition(
                name: $fullName,
                handler: $this->actions[$fullName],
                declared: RouteSourceRegistry::describeAction($handler),
                description: $description,
                flows: $flows,
                tags: $tags,
                file: isset($source['file']) ? (string) $source['file'] : $this->currentRouteFile(),
                line: isset($source['line']) ? (int) $source['line'] : null,
                relativeFile: isset($source['relative_file']) ? (string) $source['relative_file'] : null,
                group: $group ?: null,
                handlerRef: ActionHandlerRef::encode($handler),
            ));
        }

        return $fullName;
    }

    public function currentRouteFile(): ?string
    {
        $file = end($this->routeFileStack);

        return is_string($file) ? $file : null;
    }

    public function findRouteByActionReference(string $reference, ?string $collectionPrefix = null): ?string
    {
        $collectionPrefix ??= $this->currentCollection()->name;
        $normalized = str_starts_with($reference, '@') || str_starts_with($reference, '&')
            ? $reference
            : '@' . $reference;
        $targetKey = ActionReference::resolveKey($normalized, $collectionPrefix, array_keys($this->actions));

        foreach ($this->all() as $routeName => $routeCapsule) {
            $controller = $routeCapsule->getDefault('_controller');
            if (!is_string($controller) || !ActionReference::isReference($controller)) {
                continue;
            }

            $pinooxRoute = $routeCapsule->getDefault('_router');
            $prefix = $pinooxRoute instanceof Route ? $pinooxRoute->getCollection()->name : $collectionPrefix;
            $resolved = ActionReference::resolveKey($controller, $prefix, array_keys($this->actions));

            if ($resolved !== null && $resolved === $targetKey) {
                return (string) $routeName;
            }
        }

        return null;
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
     * @param array $flows
     * @param array $tags
     * @return Collection
     */
    public function collection(string $path = '', Router|string|array|callable|null $routes = null, mixed $controller = null, array|string $methods = [], array|string|Closure $action = '', array $defaults = [], array $filters = [], string $prefixName = '', array $data = [], array $flows = [], array $tags = []): Collection
    {
        $cast = $this->current;
        $prefixName = $this->buildPrefixNameCollection($prefixName);
        $defaults = $this->buildDefaultsCollection($defaults);
        $flows = $this->buildFlowsCollection($flows);
        $tags = $this->buildTagsCollection($tags);
        $filters = $this->buildFiltersCollection($filters);
        $data = $this->buildFiltersCollection($data);
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
            data: $data,
            prefixController: 'App\\' . $this->app->package() . '\\Controller',
            flows: $flows,
            tags: $tags,
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

    public function build($path, $routes): Router
    {
        $collection = $this->collection(
            path: $path,
            routes: $routes,
            prefixName: RouteNaming::collectionPrefix($this->app),
        );

        $collection->cast = -1;
        $router = new Router($this->routeName, $this->app, $collection);
        $router->appMountPath = $this->canonicalizePath($path);
        $router->actions = $this->actions;
        $router->actionMeta = $this->actionMeta;
        $router->finalizeAfterBuild($routes);

        return $router;
    }

    public function syncActionRegistry(): void
    {
        $package = (string) ($this->app->package() ?? '');
        if ($package === '') {
            return;
        }

        ActionRegistry::syncFromRouter($package, $this);

        foreach ($this->all() as $routeName => $routeCapsule) {
            $pinooxRoute = $routeCapsule->getDefault('_router');
            if ($pinooxRoute instanceof Route) {
                $this->trackActionRoute($pinooxRoute, (string) $routeName, $routeCapsule->getDefault('_controller'));
            }
        }
    }

    /**
     * @param string|array|null $routes
     */
    private function finalizeAfterBuild(string|array|null $routes): void
    {
        $this->syncActionRegistry();

        $package = (string) ($this->app->package() ?? '');

        if ($package !== '') {
            WebServerFixRegistry::flush($package);
        }

        if ($package === '') {
            return;
        }

        if ($this->shouldValidateActions()) {
            (new ActionValidator())->assertValid($this, $package, false);
        }

        $routeFiles = ActionCache::resolveRouteFiles(
            $package,
            $this->app->path(),
            is_array($routes) ? $routes : (is_string($routes) ? [$routes] : []),
        );
        if (!ActionCache::isStale($package, $routeFiles)) {
            return;
        }

        ActionCache::save($package, ActionRegistry::exportManifest($package));
    }

    private function shouldValidateActions(): bool
    {
        $package = (string) ($this->app->package() ?? '');

        try {
            return Mode::shouldValidateActions($package !== '' ? $package : null);
        } catch (\Throwable) {
            return (bool) RuntimeMode::readGlobal()['debug'];
        }
    }

    /**
     * load route file
     *
     * @param string|array $routes
     * @throws \Exception
     */
    private function loadFiles(string|array $routes): void
    {
        if (is_string($routes)) {
            $routes = $this->resolveRouteFile($routes);
            if (is_file($routes)) {
                RouteSourceRegistry::pushLoadingFile($routes);
                $this->routeFileStack[] = $routes;
                try {
                    $returned = include $routes;
                    if (is_array($returned)) {
                        $manifest = RouteManifest::isManifest($returned)
                            ? $returned
                            : (RouteManifest::isRouteList($returned) ? ['routes' => $returned] : null);

                        if ($manifest !== null) {
                            RouteManifest::apply($this, RouteManifest::normalizeManifest($manifest));
                        }
                    }
                } finally {
                    array_pop($this->routeFileStack);
                    RouteSourceRegistry::popLoadingFile();
                }
            }
        } else if (is_array($routes)) {
            foreach ($routes as $route) {
                $this->callRoutes($route);
            }
        }
    }

    private function resolveRouteFile(string $route): string
    {
        if (is_file($route)) {
            return $route;
        }

        $currentFile = end($this->routeFileStack);
        if (is_string($currentFile)) {
            $currentPath = dirname($currentFile) . DIRECTORY_SEPARATOR . $route;
            if (is_file($currentPath)) {
                return $currentPath;
            }
        }

        return $this->app->path($route);
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
     * build flow for collection
     *
     * @param array $flows
     * @return array
     */
    private function buildFlowsCollection(array $flows): array
    {
        if ($this->current > -1) {
            $flows = array_unique(array_merge($this->currentCollection()->flows, $flows));
        }
        return $flows;
    }

    /**
     * build tags for collection
     *
     * @param array $tags
     * @return array
     */
    private function buildTagsCollection(array $tags): array
    {
        if ($this->current > -1) {
            $tags = array_unique(array_merge($this->currentCollection()->tags, $tags));
        }
        return $tags;
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

    private function buildDataCollection(array $data): array
    {
        if ($this->current > -1) {
            $data = array_merge($this->currentCollection()->data, $data);
        }
        return $data;
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
        return (new PathManager($prefix))->get($path);
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
        $name = RouteNaming::localName($name, $prefix);

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
}

