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

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\RedirectResponse;
use Pinoox\Component\Router\Collection;
use Pinoox\Component\Router\Collection as ObjectPortal1;
use Pinoox\Component\Router\Route;
use Pinoox\Component\Router\RouteCollection;
use Pinoox\Component\Router\RouteName;
use Pinoox\Component\Router\Router as ObjectPortal3;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Kernel\HttpKernel;
use Pinoox\Portal\Router as ObjectPortal2;

/**
 * @method static string path(string $name, array $params = [])
 * @method static array getAllPath()
 * @method static Router add(array|string $path, \Closure|array|string $action = '', string $name = '', array|string $methods = [], array $defaults = [], array $filters = [], array $data = [])
 * @method static mixed buildAction(mixed $action, ?int $indexCollection = NULL)
 * @method static mixed getAction(string $name)
 * @method static Collection collection(string $path = '', \Pinoox\Component\Router\Router|array|callable|null|string $routes = NULL, mixed $controller = NULL, array|string $methods = [], \Closure|array|string $action = '', array $defaults = [], array $filters = [], string $prefixName = '', array $data = [])
 * @method static ObjectPortal3 build($path, $routes, array $data = [])
 * @method static \Pinoox\Component\Router\Collection|array list(string $key = '')
 * @method static Router action(string $name, \Closure|array|string $action)
 * @method static Collection currentCollection()
 * @method static \Pinoox\Component\Router\Collection|null getCollection($index = 0)
 * @method static Collection getMainCollection()
 * @method static array all()
 * @method static int count()
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

		$manager = AppEngine::manager( App::package());
		$routes = $manager->config()->get('router.routes');
		self::__bind(ObjectPortal3::class)
		    ->setArgument('routeName',static::__ref('name'))
		    ->setArgument('app',$manager);

		$route1 = self::build(
		    '/',
		    $routes,
		);



		self::defaultRoutes();
	}


	protected static function callMethod(string $method, array $args): mixed
	{
		if (!static::__has())
		    static::__register();
		return parent::callMethod($method, $args);
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
