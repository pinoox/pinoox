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

namespace Pinoox\Component\Path;

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Package\App;
use Pinoox\Component\Package\AppRouter;
use Pinoox\Component\Package\AppResourceReference;
use Pinoox\Component\Package\Reference\ReferenceInterface;
use Pinoox\Component\Path\Manager\PathManager;
use Pinoox\Component\Path\Manager\UrlManager;
use Pinoox\Component\Router\QueryRouteResolver;
use Pinoox\Component\Router\RouteNaming;

class Url implements UrlInterface
{

    public const SCOPE_APP = 'app';

    public const SCOPE_SITE = 'site';

    public const SCOPE_RELATIVE = 'relative';

    public const SCOPE_APP_PATH = 'app-path';

    public const MODE_AUTO = 'auto';

    public const MODE_CLEAN = 'clean';

    public const MODE_QUERY = 'query';

    public function __construct(
        private readonly App       $app,
        private readonly Request   $request,
        private readonly AppRouter $appRouter,
        private readonly Path      $path,
        private string             $basePath,
    )
    {
    }

    public function host(): string
    {
        return $this->request->getHost();
    }

    public function httpHost(): string
    {
        return $this->request->getHttpHost();
    }

    public function scheme(): string
    {
        return $this->request->getScheme();
    }

    public function port(): string
    {
        return (string)$this->request->getPort();
    }

    public function scriptName(): string
    {
        return $this->request->getScriptName();
    }

    public function method(): string
    {
        return $this->request->getMethod();
    }

    public function realMethod(): string
    {
        return $this->request->getRealMethod();
    }

    public function clientIp(): string
    {
        return $this->request->getClientIp();
    }

    public function userAgent(): ?string
    {
        return $this->request->server->get('HTTP_USER_AGENT');
    }

    public function clientIps(): array
    {
        return $this->request->getClientIps();
    }

    /**
     * Request base path (directory of front controller), auto-detected from the HTTP request.
     */
    public function base(): string
    {
        $basePath = $this->request->getBasePath();

        if ($basePath !== '') {
            return $basePath;
        }

        return $this->stripFrontController($this->request->getBaseUrl());
    }

    /**
     * Current request path info (route segment after base path).
     */
    public function params(): string
    {
        return $this->request->getPathInfo();
    }

    public function parameters(): array
    {
        return array_values(array_filter(explode('/', $this->params())));
    }

    /**
     * Full origin URL (scheme + host + base path), auto-detected from the HTTP request.
     */
    public function origin(bool $absolute = true): string
    {
        if (!$absolute) {
            return $this->base();
        }

        $origin = $_ENV['HOST_PROXY'] ?? $this->request->getUriForPath('');

        if ($this->isSecure() && str_contains($origin, 'http:')) {
            $origin = str_replace('http:', 'https:', $origin);
        }

        return $this->stripFrontController($origin);
    }

    /**
     * Base URL for the active app or a specific package (resolved via AppRouter).
     */
    public function forApp(?string $package = null): string
    {
        $package = $package ?? $this->app->package();
        $route = $this->routeSegmentForPackage($package);

        if ($route === '') {
            return rtrim($this->origin(), '/');
        }

        return rtrim($this->origin(), '/') . '/' . $route;
    }

    /** Resolve scoped or active app package name. */
    public function activePackage(?string $package = null): string
    {
        return ($package !== null && $package !== '') ? $package : $this->app->package();
    }

    /**
     * Public path to the project root (leading slash, never empty).
     */
    public function sitePath(): string
    {
        $base = rtrim($this->base(), '/');

        return $base === '' ? '/' : $base;
    }

    /**
     * App route segment from App Router (e.g. "manager" for "/manager").
     */
    public function routeSegment(?string $package = null): string
    {
        return $this->routeSegmentForPackage($package ?? $this->app->package());
    }

    /**
     * Public path to the app base (site path + app segment).
     */
    public function appPath(?string $package = null): string
    {
        return $this->joinPublicPath($this->sitePath(), $this->routeSegment($package));
    }

    /**
     * Fluent URL accessor for the active request and app route.
     */
    public function accessor(?string $package = null): UrlAccessor
    {
        return new UrlAccessor($this, $package);
    }

    /** Fluent theme accessor (see global theme() helper). */
    public function themeAccessor(?string $name = null, ?string $package = null): ThemeAccessor
    {
        return new ThemeAccessor($this, $package, $name);
    }

    /** Fluent app manifest accessor (see global package() helper). */
    public function appAccessor(?string $package = null): AppAccessor
    {
        return new AppAccessor($this, $package);
    }

    /**
     * Smart URL builder: handles references, query-route fallback, and rewrite-aware routing.
     */
    public function link(string $link = '', string $scope = self::SCOPE_APP, string $mode = self::MODE_AUTO): string
    {
        if ($link !== '' && str_starts_with($link, '~')) {
            return $this->reference($link);
        }

        if (str_starts_with($link, '?')) {
            $fromQuery = $this->linkFromQueryString($link, $scope, $mode);

            if ($fromQuery !== null) {
                return $fromQuery;
            }
        }

        if ($this->isRoutePath($link)) {
            return $this->linkForRoutePath($link, $scope, $mode);
        }

        return $this->to($link, $scope);
    }

    public function isRoutePath(string $link): bool
    {
        if ($link === '' || $link === '/') {
            return false;
        }

        return !str_starts_with($link, '~')
            && !str_starts_with($link, '?')
            && !str_starts_with($link, 'http://')
            && !str_starts_with($link, 'https://')
            && !str_starts_with($link, '//')
            && !str_starts_with($link, '#');
    }

    /**
     * Build a URL relative to site root, active app, or request base path.
     */
    public function to(string $path = '', string $scope = self::SCOPE_APP): string
    {
        $manager = new UrlManager();
        $manager->setBasePath(match ($scope) {
            self::SCOPE_SITE => rtrim($this->origin(), '/'),
            self::SCOPE_RELATIVE => rtrim($this->base(), '/'),
            self::SCOPE_APP_PATH => $this->appPath(),
            default => $this->forApp(),
        });

        $built = $manager->get(ltrim($path, '/'));

        if (($scope === self::SCOPE_RELATIVE || $scope === self::SCOPE_APP_PATH)
            && $built !== ''
            && !str_starts_with($built, '/')) {
            return '/' . $built;
        }

        return $built;
    }

    /**
     * Public asset URL under apps/{package}/ (direct filesystem exposure, not app route).
     */
    public function asset(string $path = '', ?string $package = null): string
    {
        $path = $this->normalizeAppPublicPath($path, $package);
        $manager = new UrlManager();
        $manager->setBasePath(rtrim($this->origin(), '/'));

        return $manager->get(ltrim($path, '/'));
    }

    /**
     * Path-only public asset URL under apps/{package}/.
     */
    public function assetPath(string $path = '', ?string $package = null): string
    {
        $path = $this->normalizeAppPublicPath($path, $package);
        $manager = new UrlManager();
        $manager->setBasePath($this->sitePath());
        $built = $manager->get(ltrim($path, '/'));

        if ($built !== '' && !str_starts_with($built, '/')) {
            return '/' . $built;
        }

        return $built;
    }

    /**
     * Normalize app-relative public paths to apps/{package}/… segments.
     */
    public function normalizeAppPublicPath(string $path, ?string $package = null): string
    {
        $package = $package ?? $this->app->package();
        $path = str_replace('\\', '/', trim($path));
        $projectRoot = rtrim(str_replace('\\', '/', $this->basePath), '/');

        if ($projectRoot !== '' && str_starts_with($path, $projectRoot)) {
            $path = ltrim(substr($path, $projectRoot), '/');
        }

        if (str_starts_with($path, '~/')) {
            $path = ltrim($this->path->get($path, $package), '/');
        }

        $appsPrefix = $this->appsPublicPrefix();

        if (preg_match('#^' . preg_quote($appsPrefix, '#') . '/([^/]+)(?:/(.*))?$#', $path, $matches)) {
            $relative = ltrim((string) ($matches[2] ?? ''), '/');

            return $relative === ''
                ? $appsPrefix . '/' . $matches[1]
                : $appsPrefix . '/' . $matches[1] . '/' . $relative;
        }

        if ($package === '') {
            return ltrim($path, '/');
        }

        $relative = ltrim($path, '/');

        return $relative === ''
            ? $appsPrefix . '/' . $package
            : $appsPrefix . '/' . $package . '/' . $relative;
    }

    private function appsPublicPrefix(): string
    {
        $appsRoot = rtrim(str_replace('\\', '/', $this->path->get('~apps')), '/');
        $projectRoot = rtrim(str_replace('\\', '/', $this->basePath), '/');

        if ($projectRoot !== '' && str_starts_with($appsRoot, $projectRoot)) {
            return ltrim(substr($appsRoot, strlen($projectRoot)), '/');
        }

        return 'apps';
    }

    /**
     * Convert an absolute filesystem path under the project root to a public URL.
     */
    public function fromPath(string $filesystemPath): string
    {
        $filesystemPath = str_replace('\\', '/', $filesystemPath);
        $projectRoot = rtrim(str_replace('\\', '/', $this->basePath), '/');

        if (str_starts_with($filesystemPath, $projectRoot)) {
            $relative = ltrim(substr($filesystemPath, strlen($projectRoot)), '/');
        } else {
            $relative = ltrim($filesystemPath, '/');
        }

        $manager = new UrlManager();
        $manager->setBasePath(rtrim($this->origin(), '/'));

        return $manager->get($relative);
    }

    /**
     * Resolve a path reference to its public URL (filesystem ref or app-relative segment).
     */
    public function reference(string|ReferenceInterface $ref, ?string $package = null): string
    {
        $reference = $this->path->reference($ref);
        $filesystemPath = $this->path->get($reference, $package);

        if ($resolvedPackage = $this->packageFromFilesystemPath($filesystemPath)) {
            $appRoot = rtrim($this->path->app($resolvedPackage), '/');
            $relative = ltrim(substr($filesystemPath, strlen($appRoot)), '/');

            return $this->asset($relative, $resolvedPackage);
        }

        return $this->fromPath($filesystemPath);
    }

    public function route(string $name, array $parameters = [], bool $absolute = true, ?string $package = null): string
    {
        $package ??= (string) ($this->app->package() ?? '');
        $name = RouteNaming::full($name, $package !== '' ? $package : null);

        $manager = new UrlManager();
        $manager->setBasePath($absolute ? $this->origin() : rtrim($this->base(), '/'));

        return $manager->get($this->app->router()->path($name, $parameters));
    }

    public function action(string $actionReference, array $parameters = [], bool $absolute = true): string
    {
        if ($cross = AppResourceReference::parseActionReference($actionReference)) {
            return $this->actionForPackage($cross['package'], '@' . $cross['action'], $parameters, $absolute);
        }

        $routeName = $this->app->router()->findRouteByActionReference($actionReference);
        if ($routeName === null) {
            throw new \InvalidArgumentException(sprintf('No route is bound to action "%s".', $actionReference));
        }

        return $this->route($routeName, $parameters, $absolute);
    }

    public function actionForPackage(string $package, string $actionReference, array $parameters = [], bool $absolute = true): string
    {
        return $this->app->meeting($package, function () use ($actionReference, $parameters, $absolute, $package) {
            $routeName = $this->app->router()->findRouteByActionReference($actionReference);
            if ($routeName === null) {
                throw new \InvalidArgumentException(sprintf(
                    'No route is bound to action "%s" in package "%s".',
                    $actionReference,
                    $package,
                ));
            }

            return $this->route($routeName, $parameters, $absolute);
        });
    }

    public function appUrls(string $package): array
    {
        $routes = $this->appRouter->getByPackage($package) ?? [];
        $origin = rtrim($this->origin(), '/');

        return array_values(array_map(
            static fn(string $routePath): string => $origin . $routePath,
            array_keys($routes),
        ));
    }

    public function appUrl(string $package): ?string
    {
        $urls = $this->appUrls($package);

        return $urls[0] ?? null;
    }

    public function check(?string $link, ?string $default = null): ?string
    {
        if (!empty($link) && $this->existsFile($link)) {
            return $link;
        }

        return $default;
    }

    public function existsFile(?string $link): bool
    {
        if (empty($link)) {
            return false;
        }

        if (Str::firstHas($link, $this->origin())) {
            $link = Str::firstDelete($link, $this->origin());
        }

        $pathManager = new PathManager($this->basePath);
        $file = $pathManager->get(ltrim($link, '/'));

        return is_file($file);
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function pathWithoutBase(): string
    {
        $path = $this->request->getPathInfo();
        $basePath = $this->app->pathRoute();

        if (!empty($basePath) && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim((string)$path, '/');
        }

        return $path;
    }

    public function referer(): ?string
    {
        return $this->request->headers->get('referer');
    }

    public function current(): string
    {
        return $this->request->getUri();
    }

    public function isSecure(): bool
    {
        return $this->request->isSecure();
    }

    public function isQueryRoute(): bool
    {
        return $this->request->isQueryRoute();
    }

    public function queryRoute(string $path = '', bool $absolute = true): string
    {
        return QueryRouteResolver::buildUrl($this->origin($absolute), $path);
    }

    public function queryRouteForApp(string $path, ?string $package = null, bool $absolute = true): string
    {
        return QueryRouteResolver::buildUrl($this->forApp($package), $path);
    }

    private function linkFromQueryString(string $link, string $scope, string $mode): ?string
    {
        parse_str(ltrim($link, '?'), $query);
        $parameter = QueryRouteResolver::parameter();
        $route = $query[$parameter] ?? null;

        if (!is_string($route) || trim($route) === '') {
            return null;
        }

        return $this->resolveRoutedPath($route, $scope, $mode);
    }

    private function linkForRoutePath(string $link, string $scope, string $mode): string
    {
        return $this->resolveRoutedPath($link, $scope, $mode);
    }

    private function resolveRoutedPath(string $path, string $scope, string $mode): string
    {
        $effectiveMode = $this->normalizeLinkMode($mode);

        if ($effectiveMode === self::MODE_CLEAN) {
            return $this->to($path, $scope);
        }

        if ($effectiveMode === self::MODE_QUERY) {
            return $this->buildQueryRouteLink($path, $scope);
        }

        if (QueryRouteResolver::rewriteAppearsActive()) {
            return $this->to($path, $scope);
        }

        return $this->buildQueryRouteLink($path, $scope);
    }

    private function buildQueryRouteLink(string $path, string $scope): string
    {
        if ($scope === self::SCOPE_RELATIVE) {
            $resolved = QueryRouteResolver::resolvePath($path);
            $parameter = QueryRouteResolver::parameter();
            $base = rtrim($this->base(), '/');

            return ($base === '' ? '' : $base) . '/?' . $parameter . '=' . rawurlencode($resolved);
        }

        if ($scope === self::SCOPE_APP_PATH) {
            return QueryRouteResolver::buildUrl($this->appPath(), $path);
        }

        return $this->queryRoute($path);
    }

    private function joinPublicPath(string $basePath, string $segment): string
    {
        $segment = trim($segment, '/');

        if ($segment === '') {
            return $basePath;
        }

        if ($basePath === '/') {
            return '/' . $segment;
        }

        return $basePath . '/' . $segment;
    }

    private function normalizeLinkMode(string $mode): string
    {
        return match ($mode) {
            self::MODE_CLEAN, self::MODE_QUERY => $mode,
            default => self::MODE_AUTO,
        };
    }

    private function routeSegmentForPackage(?string $package): string
    {
        if ($package === null || $package === '') {
            return '';
        }

        if ($package === $this->app->package()) {
            return ltrim((string)$this->app->pathRoute(), '/');
        }

        $routes = $this->appRouter->getByPackage($package) ?? [];
        if ($routes === []) {
            return '';
        }

        $routePath = (string)array_key_first($routes);

        return ltrim($routePath, '/');
    }

    private function packageFromFilesystemPath(string $filesystemPath): ?string
    {
        $filesystemPath = str_replace('\\', '/', $filesystemPath);
        $appsRoot = rtrim(str_replace('\\', '/', $this->path->get('~apps')), '/');

        if (!str_starts_with($filesystemPath, $appsRoot . '/')) {
            return null;
        }

        $remainder = substr($filesystemPath, strlen($appsRoot) + 1);
        $segment = strtok($remainder, '/');

        return is_string($segment) && $segment !== '' ? $segment : null;
    }

    private function stripFrontController(string $value): string
    {
        if ($value === '' || !str_contains($value, 'index.php')) {
            return $value;
        }

        $stripped = preg_replace('#/index\.php(?=/|$)#', '', $value);

        return is_string($stripped) && $stripped !== '' ? $stripped : $value;
    }
}

