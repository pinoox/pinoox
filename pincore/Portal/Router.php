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

use Pinoox\Component\Router\Collection;
use Pinoox\Component\Router\RouteName;
use Pinoox\Component\Router\Router as ObjectPortal3;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Router as ObjectPortal2;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as ObjectPortal1;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface as ObjectPortal4;

/**
 * @method static ObjectPortal1 getUrlGenerator(?\Symfony\Component\Routing\RequestContext $context = NULL)
 * @method static string path(string $name, array $params = [], ?\Pinoox\Component\Http\Request $request = NULL)
 * @method static ObjectPortal4 getUrlMatcher(?\Symfony\Component\Routing\RequestContext $context = NULL)
 * @method static array match(string $path, ?\Pinoox\Component\Http\Request $request = NULL)
 * @method static array matchRequest(\Pinoox\Component\Http\Request $request)
 * @method static array getAllPath()
 * @method static Router add(array|string $path, \Closure|array|string $action = '', string $name = '', array|string $methods = [], array $defaults = [], array $filters = [], ?int $property = null, array $data = [])
 * @method static mixed buildAction(mixed $action, ?int $indexCollection = NULL)
 * @method static mixed getAction(string $name)
 * @method static Collection collection(string $path = '', \Pinoox\Component\Router\Router|array|callable|null|string $routes = NULL, mixed $controller = NULL, array|string $methods = [], \Closure|array|string $action = '', array $defaults = [], array $filters = [], string $prefixName = '', array $data = [])
 * @method static string canonicalizePath(string $path)
 * @method static ObjectPortal3 build($path, $routes, array $data = [])
 * @method static Router action(string $name, \Closure|array|string $action)
 * @method static Collection currentCollection()
 * @method static \Pinoox\Component\Router\Collection|null getCollection($index = 0)
 * @method static array all()
 * @method static int count()
 * @method static ObjectPortal2 get(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [])
 * @method static ObjectPortal2 post(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [])
 * @method static ObjectPortal2 put(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [])
 * @method static ObjectPortal2 patch(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [])
 * @method static ObjectPortal2 delete(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [])
 * @method static ObjectPortal2 options(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [])
 * @method static ObjectPortal2 head(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [])
 * @method static ObjectPortal2 purge(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [])
 * @method static ObjectPortal2 trace(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [])
 * @method static ObjectPortal2 connect(array|string $path, \Closure|array|string $action = '', string $name = '', array $defaults = [], array $filters = [], ?int $property = null, array $data = [])
 * @method static \Pinoox\Component\Router\RouteName ___name()
 * @method static \Pinoox\Component\Router\Router ___()
 *
 * @see \Pinoox\Component\Router\Router
 */
class Router extends Portal
{
    public static function __register(): void
    {
        self::__bind(RouteName::class, 'name');

        self::__bind(ObjectPortal3::class)
            ->setArgument('routeName', static::__ref('name'))
            ->setArgument('app', App::__ref());
    }

    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'router';
    }

    public static function __replace(): array
    {
        return [
            'get|post|put|patch|delete|options|head|purge|trace|connect' => function (
                array|string          $path,
                \Closure|array|string $action = '',
                string                $name = '',
                array                 $defaults = [],
                array                 $filters = [],
                ?int                  $property = null,
                array                 $data = []
            ): Router {
                return self::add($path, $action, $name, self::$__method, $defaults, $filters,$property,$data);
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
            'action'
        ];
    }
}
