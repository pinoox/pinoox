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

use Pinoox\Portal\Url;
use Pinoox\Component\Router\QueryRouteResolver;
use Pinoox\Component\Path\ThemeAccessor;
use Pinoox\Component\Path\AppAccessor;
use Pinoox\Portal\Path;
use Pinoox\Portal\View;

if (!function_exists('url_is_route_path')) {
    function url_is_route_path(string $link): bool
    {
        return Url::isRoutePath($link);
    }
}

if (!function_exists('rewrite_active')) {
    function rewrite_active(): bool
    {
        return QueryRouteResolver::rewriteAppearsActive();
    }
}

if (!function_exists('app')) {
    /**
     * Fluent app manifest accessor (app.php).
     *
     * @example app().name
     * @example app().theme().name
     * @example app('com_pinoox_welcome').url()
     */
    function app(?string $package = null): AppAccessor
    {
        return Url::appAccessor($package);
    }
}

if (!function_exists('package')) {
    /** @deprecated Use app() */
    function package(?string $package = null): AppAccessor
    {
        return app($package);
    }
}

if (!function_exists('url')) {
    /**
     * @return UrlAccessor|string
     */
    function url(?string $link = null, string $scope = Url::SCOPE_APP, string $mode = Url::MODE_AUTO): \Pinoox\Component\Path\UrlAccessor|string
    {
        if ($link === null && func_num_args() === 0) {
            return Url::accessor();
        }

        return Url::link($link ?? '', $scope, $mode);
    }
}

if (!function_exists('theme')) {
    /**
     * Fluent theme accessor (theme.php). Defaults to app().theme().
     *
     * @example theme().name
     * @example theme('spark').assets('index.html')
     */
    function theme(?string $name = null, ?string $package = null): ThemeAccessor
    {
        return app($package)->theme($name);
    }
}

if (!function_exists('assets_is_filesystem_path')) {
    function assets_is_filesystem_path(string $path): bool
    {
        return View::isFilesystemPath($path);
    }
}

if (!function_exists('assets')) {
    /**
     * @param string $link Relative file, @theme/file, or @com_pkg:theme/file for cross-theme assets.
     */
    function assets(string $link = '', bool $isPath = false, ?string $theme = null): string
    {
        return View::assets($link, $isPath, $theme);
    }
}

if (!function_exists('asset')) {
    function asset(string $path = '', ?string $package = null): string
    {
        return app($package)->resource($path);
    }
}

if (!function_exists('path')) {
    function path(string $reference = '', ?string $package = ''): string
    {
        return Path::get($reference, $package);
    }
}

if (!function_exists('database_path')) {
    /**
     * Absolute path to the project database directory or a file inside it.
     *
     * Laravel-compatible helper for sqlite paths in database.config.php.
     */
    function database_path(string $path = ''): string
    {
        $base = path('~storage/app/database');

        if ($path === '') {
            return $base;
        }

        return $base . '/' . ltrim(str_replace('\\', '/', $path), '/');
    }
}

if (!function_exists('app_urls')) {
    function app_urls(string $package): array
    {
        return Url::appUrls($package);
    }
}

if (!function_exists('app_url')) {
    function app_url(string $package): ?string
    {
        return Url::appUrl($package);
    }
}
