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

class QueryRouteResolver
{
    private const SERVER_FLAG = 'PINOOX_QUERY_ROUTE_APPLIED';

    private static ?array $config = null;

    private static ?string $rawRoute = null;

    private static ?string $resolvedPath = null;

    public static function defaultConfig(): array
    {
        return [
            'parameter' => 'route',
            'prefer_path_info' => true,
            'path_aliases' => [],
            'prefix_rules' => [],
        ];
    }

    public static function parameter(): string
    {
        return self::config()['parameter'] ?? 'route';
    }

    public static function package(): ?string
    {
        return self::config()['package'] ?? null;
    }

    public static function applyToGlobals(): ?string
    {
        if (PHP_SAPI === 'cli') {
            return null;
        }

        $parameter = self::parameter();
        $route = $_GET[$parameter] ?? null;

        if (!is_string($route) || trim($route) === '') {
            return null;
        }

        if ((self::config()['prefer_path_info'] ?? true) && self::hasResolvedPathInfo()) {
            return null;
        }

        self::$rawRoute = $route;
        self::$resolvedPath = self::resolvePath($route);

        $_SERVER['PATH_INFO'] = self::$resolvedPath;
        $_SERVER[self::SERVER_FLAG] = '1';

        return self::$resolvedPath;
    }

    public static function wasApplied(): bool
    {
        return self::$resolvedPath !== null
            || (isset($_SERVER[self::SERVER_FLAG]) && $_SERVER[self::SERVER_FLAG] === '1');
    }

    public static function rawRoute(): ?string
    {
        return self::$rawRoute;
    }

    public static function resolvedPath(): ?string
    {
        return self::$resolvedPath;
    }

    public static function resolvePath(string $route, ?string $package = null): string
    {
        $path = self::normalize($route);
        $path = self::applyPathAliases($path, $package);
        $path = self::applyPrefixRules($path, $package);

        return $path;
    }

    public static function normalize(string $route): string
    {
        $route = rawurldecode(trim($route));

        if ($route === '' || $route === '/') {
            return '/';
        }

        $route = '/' . ltrim($route, '/');
        $route = preg_replace('#/+#', '/', $route) ?? $route;

        if (str_contains($route, '..')) {
            return '/';
        }

        if ($route !== '/') {
            $route = rtrim($route, '/');
        }

        return $route;
    }

    public static function buildUrl(string $siteUrl, string $routePath, ?string $package = null): string
    {
        $siteUrl = rtrim($siteUrl, '/');
        $path = self::normalize($routePath);
        $parameter = self::parameter();

        return $siteUrl . '/?' . $parameter . '=' . rawurlencode($path);
    }

    private static function applyPrefixRules(string $path, ?string $package = null): string
    {
        $rules = self::config($package)['prefix_rules'] ?? [];

        foreach ($rules as $rule) {
            $prefix = $rule['prefix'] ?? '';

            if ($prefix === '') {
                continue;
            }

            foreach ($rule['unless_starts_with'] ?? [] as $startsWith) {
                if ($startsWith !== '' && str_starts_with($path, $startsWith)) {
                    continue 2;
                }
            }

            if (in_array($path, $rule['unless_equals'] ?? [], true)) {
                continue;
            }

            if (!str_starts_with($path, $prefix)) {
                return rtrim($prefix, '/') . $path;
            }
        }

        return $path;
    }

    private static function applyPathAliases(string $path, ?string $package = null): string
    {
        $aliases = self::config($package)['path_aliases'] ?? [];

        return $aliases[$path] ?? $path;
    }

    private static function hasResolvedPathInfo(): bool
    {
        if (!empty($_SERVER['REDIRECT_URL']) || !empty($_SERVER['REDIRECT_STATUS'])) {
            return true;
        }

        $pathInfo = $_SERVER['PATH_INFO'] ?? '';

        if ($pathInfo !== '' && $pathInfo !== '/') {
            return true;
        }

        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $requestPath = str_replace('\\', '/', $requestPath);
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        if ($scriptName === '' || $requestPath === '') {
            return false;
        }

        $basePath = rtrim(dirname($scriptName), '/');
        if ($basePath !== '' && $basePath !== '/' && str_starts_with($requestPath, $basePath)) {
            $requestPath = substr($requestPath, strlen($basePath));
        }

        if (str_ends_with($requestPath, '/index.php')) {
            $requestPath = substr($requestPath, 0, -10) ?: '/';
        }

        $requestPath = rtrim($requestPath, '/');

        return $requestPath !== '' && $requestPath !== '/';
    }

    private static function config(?string $package = null): array
    {
        if ($package !== null) {
            return array_replace_recursive(
                self::defaultConfig(),
                QueryRouteConfigLoader::loadPackageConfig($package)
            );
        }

        if (is_array(self::$config)) {
            return self::$config;
        }

        return self::$config = QueryRouteConfigLoader::forRequest();
    }
}
