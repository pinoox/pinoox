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

namespace pinoox\portal;

use pinoox\component\Helpers\Str;
use pinoox\component\Http\RedirectResponse;
use pinoox\component\router\Collection as ObjectPortal1;
use pinoox\component\router\Route;
use pinoox\component\source\Portal;
use pinoox\portal\Router as ObjectPortal2;
use pinoox\portal\app\App;
use pinoox\portal\app\AppEngine;
use pinoox\portal\kernel\HttpKernel;

/**
 * @method static string path(string $name, array $params = [])
 * @method static Router add(array|string $path, \Closure|array|string $action = '', string $name = '', array|string $methods = [], array $defaults = [], array $filters = [])
 * @method static mixed buildAction(mixed $action, ?int $indexCollection = NULL)
 * @method static mixed getAction(string $name)
 * @method static Router collection(string $path = '', array|callable|null|\pinoox\component\router\Router|string $routes = NULL, mixed $controller = NULL, array|string $methods = [], \Closure|array|string $action = '', array $defaults = [], array $filters = [], string $prefixName = '')
 * @method static Router action(string $name, \Closure|array|string $action)
 * @method static ObjectPortal1 currentCollection()
 * @method static \pinoox\component\router\Collection|null getCollection($index = 0)
 * @method static ObjectPortal1 getMainCollection()
 * @method static string generateName(?\pinoox\component\router\Collection $collection = NULL)
 * @method static array all()
 * @method static string generateRandomName(string $prefix = '')
 * @method static ObjectPortal2 get(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [])
 * @method static ObjectPortal2 post(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [])
 * @method static ObjectPortal2 put(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [])
 * @method static ObjectPortal2 patch(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [])
 * @method static ObjectPortal2 delete(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [])
 * @method static ObjectPortal2 options(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [])
 * @method static ObjectPortal2 head(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [])
 * @method static ObjectPortal2 purge(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [])
 * @method static ObjectPortal2 trace(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [])
 * @method static ObjectPortal2 connect(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [])
 * @method static \pinoox\component\router\Router ___()
 *
 * @see \pinoox\component\router\Router
 */
class Router extends Portal
{
    public static function __register(): void
    {
        self::register(
            App::package(),
            App::path()
        );
    }


    protected static function callMethod(string $method, array $args): mixed
    {
        if (!static::__has())
            static::__register();
        return parent::callMethod($method, $args);
    }


    private static function register(string $package, string $path): void
    {
        if (self::__has())
            return;
        $manager = AppEngine::manager($package);
        $routes = $manager->config()->get('router.routes');
        self::__bind(\pinoox\component\router\Router::class)
            ->setArguments([
                $manager,
                App::current()
            ])
            ->addMethodCall('collection', [
                $path,
                $routes
            ]);

        self::defaultRoutes();
    }


    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return !empty(App::package()) ? 'router.' . App::package() . '('.App::path().')' : 'router';
    }


    public static function __replace(): array
    {
        return [
            'get|post|put|patch|delete|options|head|purge|trace|connect' => function (
                array|string          $path,
                \Closure|array|string $action = '',
                string                $name = '',
                array                 $defaults = [],
                array                 $filters = []
            ): Router {
                return self::add($path, $action, $name, self::$__method, $defaults, $filters);
            },
        ];
    }


    /**
     * Get exclude method names .
     * @return string[]
     */
    public static function __exclude(): array
    {
        return [];
    }


    /**
     * Get method names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [
            'add',
            'collection',
            'action'
        ];
    }


    private static function defaultRoutes(): void
    {
        $pathCollection = self::getMainCollection()->path;
        $paths = ['/{slash_remover}/'];
        if (!empty($pathCollection) && $pathCollection !== '/')
            $paths[] = '//';

        self::add(
            path: $paths,
            action: function (Route $route, $slash_remover = '') {
                $slug = $route->getCollection()->path . '/' . $slash_remover;
                $slug = Str::lastDelete($slug, '/');
                return new RedirectResponse('~' . $slug, 301);
            },
            filters: [
                'slash_remover' => '.+/'
            ]
        );
    }
}
