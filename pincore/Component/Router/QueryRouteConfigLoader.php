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

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Package\Routing\AppRouteMatcher;
use Pinoox\Component\Package\Routing\Domain;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\SystemConfig;

class QueryRouteConfigLoader
{
    private static ?array $resolved = null;

    public static function forRequest(): array
    {
        if (is_array(self::$resolved)) {
            return self::$resolved;
        }

        $config = QueryRouteResolver::defaultConfig();
        $package = self::resolvePackageName();

        if ($package !== null) {
            $appConfig = self::loadPackageConfig($package);

            if (!empty($appConfig)) {
                $config = array_replace_recursive($config, $appConfig);
            }

            $config['package'] = $package;
        }

        return self::$resolved = $config;
    }

    public static function loadPackageConfig(string $package): array
    {
        $basePath = Loader::getBasePath();

        if (!is_string($basePath) || $basePath === '') {
            return [];
        }

        $appPath = AppEngine::exists($package)
            ? AppEngine::path($package)
            : SystemConfig::path('apps') . '/' . $package;
        $file = rtrim(str_replace('\\', '/', $appPath), '/') . '/config/query_route.config.php';

        if (!is_file($file)) {
            return [];
        }

        $loaded = require $file;

        return is_array($loaded) ? $loaded : [];
    }

    private static function resolvePackageName(): ?string
    {
        $host = Domain::normalizeHost((string)($_SERVER['HTTP_HOST'] ?? ''));
        $domainMatch = Domain::match($host);

        if ($domainMatch !== null && self::isStable($domainMatch->package)) {
            return $domainMatch->package;
        }

        $routes = self::loadRouterMap();

        if ($routes === []) {
            return null;
        }

        $routes = AppRouteMatcher::normalizeRoutes($routes);
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';

        if ($pathInfo !== '' && $pathInfo !== '/') {
            $match = AppRouteMatcher::match($pathInfo, $routes, static fn(string $package): bool => self::isStable($package));

            return $match['package'] ?? ($routes['/'] ?? null);
        }

        foreach (self::requestSegments() as $segment) {
            $match = AppRouteMatcher::match($segment, $routes, static fn(string $package): bool => self::isStable($package));

            if ($match !== null) {
                return $match['package'];
            }
        }

        return $routes['/'] ?? $routes['*'] ?? null;
    }

    private static function isStable(string $package): bool
    {
        if (!AppEngine::exists($package)) {
            return false;
        }

        try {
            return (bool)AppEngine::config($package)->get('enable');
        } catch (\Throwable) {
            return false;
        }
    }

    private static function requestSegments(): array
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
        $path = str_replace('\\', '/', $path);

        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        if (str_ends_with($path, '/index.php')) {
            $path = substr($path, 0, -10);
        }

        return array_values(array_filter(explode('/', trim($path, '/'))));
    }

    private static function loadRouterMap(): array
    {
        $basePath = Loader::getBasePath();

        if (!is_string($basePath) || $basePath === '') {
            return [];
        }

        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        $systemRouter = SystemConfig::path('system_router');

        $candidates = [
            $basePath . '/pinker/system/config/app/router.config.php',
            $systemRouter,
        ];

        foreach ($candidates as $file) {
            if (!is_file($file)) {
                continue;
            }

            $routes = require $file;

            return is_array($routes) ? $routes : [];
        }

        return [];
    }
}
