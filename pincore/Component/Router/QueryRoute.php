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

class QueryRoute
{
    public function parameter(): string
    {
        return QueryRouteResolver::parameter();
    }

    public function package(): ?string
    {
        return QueryRouteResolver::package();
    }

    public function applyToGlobals(): ?string
    {
        return QueryRouteResolver::applyToGlobals();
    }

    public function wasApplied(): bool
    {
        return QueryRouteResolver::wasApplied();
    }

    public function rawRoute(): ?string
    {
        return QueryRouteResolver::rawRoute();
    }

    public function resolvedPath(): ?string
    {
        return QueryRouteResolver::resolvedPath();
    }

    public function resolvePath(string $route, ?string $package = null): string
    {
        return QueryRouteResolver::resolvePath($route, $package);
    }

    public function normalize(string $route): string
    {
        return QueryRouteResolver::normalize($route);
    }

    public function buildUrl(string $siteUrl, string $routePath): string
    {
        return QueryRouteResolver::buildUrl($siteUrl, $routePath);
    }

    public function packageConfig(string $package): array
    {
        return array_replace_recursive(
            QueryRouteResolver::defaultConfig(),
            QueryRouteConfigLoader::loadPackageConfig($package)
        );
    }
}

