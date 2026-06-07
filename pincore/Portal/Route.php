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

use Closure;
use Pinoox\Component\Router\RouteBuilder;
use Pinoox\Component\Router\RouteEntryBuilder;
use Pinoox\Component\Router\RouteRegistrar;
use Pinoox\Component\Source\Portal;

/**
 * Laravel-style route definitions for Pinoox apps.
 *
 * Route::get('/', '@home')->name('home');
 * Route::group(['prefix' => 'api', 'flow' => 'auth'], fn () => ...);
 * Route::collect(fn () => ...);
 *
 * @see RouteRegistrar
 */
class Route extends Portal
{
    private static ?RouteRegistrar $registrar = null;

    private static function registrar(): RouteRegistrar
    {
        return self::$registrar ??= new RouteRegistrar();
    }

    public static function get(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return self::registrar()->get($path, $action);
    }

    public static function post(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return self::registrar()->post($path, $action);
    }

    public static function put(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return self::registrar()->put($path, $action);
    }

    public static function patch(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return self::registrar()->patch($path, $action);
    }

    public static function delete(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return self::registrar()->delete($path, $action);
    }

    public static function match(array|string $methods, string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return self::registrar()->match($methods, $path, $action);
    }

    public static function group(array $attributes, callable $callback): void
    {
        self::registrar()->group($attributes, $callback);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function collect(callable $callback): array
    {
        return self::registrar()->collect($callback);
    }

    public static function __register(): void
    {
        self::__bind(RouteRegistrar::class);
    }

    public static function __name(): string
    {
        return 'route';
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

