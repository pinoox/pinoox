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

use Pinoox\Component\Path\Url;
use Pinoox\Component\Router\QueryRouteResolver;
use Pinoox\Portal\Path;
use Pinoox\Portal\View;

if (!function_exists('url_is_route_path')) {
    function url_is_route_path(string $link): bool
    {
        return \Pinoox\Portal\Url::isRoutePath($link);
    }
}

if (!function_exists('rewrite_active')) {
    function rewrite_active(): bool
    {
        return QueryRouteResolver::rewriteAppearsActive();
    }
}

if (!function_exists('url')) {
    function url(string $link = '', string $scope = Url::SCOPE_APP, string $mode = Url::MODE_AUTO): string
    {
        return \Pinoox\Portal\Url::link($link, $scope, $mode);
    }
}

if (!function_exists('assets_is_filesystem_path')) {
    function assets_is_filesystem_path(string $path): bool
    {
        return View::isFilesystemPath($path);
    }
}

if (!function_exists('assets')) {
    function assets(string $link = '', bool $isPath = false): string
    {
        return View::assets($link, $isPath);
    }
}

if (!function_exists('asset')) {
    function asset(string $path = '', ?string $package = null): string
    {
        return \Pinoox\Portal\Url::asset($path, $package);
    }
}

if (!function_exists('path')) {
    function path(string $reference = '', ?string $package = ''): string
    {
        return Path::get($reference, $package);
    }
}

if (!function_exists('app_urls')) {
    function app_urls(string $package): array
    {
        return \Pinoox\Portal\Url::appUrls($package);
    }
}

if (!function_exists('app_url')) {
    function app_url(string $package): ?string
    {
        return \Pinoox\Portal\Url::appUrl($package);
    }
}
