<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Portal;

use Pinoox\Component\Router\QueryRoute as QueryRouteService;
use Pinoox\Component\Source\Portal;

/**
 * @method static string parameter()
 * @method static string|null applyToGlobals()
 * @method static bool wasApplied()
 * @method static string|null rawRoute()
 * @method static string|null resolvedPath()
 * @method static string|null package()
 * @method static string resolvePath(string $route, ?string $package = NULL)
 * @method static string buildUrl(string $siteUrl, string $routePath)
 * @method static array packageConfig(string $package)
 * @method static \Pinoox\Component\Router\QueryRoute ___()
 *
 * @see \Pinoox\Component\Router\QueryRoute
 */
class QueryRoute extends Portal
{
    public static function __register(): void
    {
        self::__bind(QueryRouteService::class);
    }

    public static function __name(): string
    {
        return 'query_route';
    }

    public static function __exclude(): array
    {
        return [];
    }

    public static function __callback(): array
    {
        return [];
    }
}

