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

namespace Pinoox\Portal\App;

use Composer\Autoload\ClassLoader;
use Pinoox\Component\Http\Request as ObjectPortal7;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Kernel\LoaderManager;
use Pinoox\Component\Lang\Lang as ObjectPortal8;
use Pinoox\Component\Package\AppLayer;
use Pinoox\Component\Package\AppManager as ObjectPortal1;
use Pinoox\Component\Package\AppRouter as ObjectPortal6;
use Pinoox\Component\Router\Collection as ObjectPortal4;
use Pinoox\Component\Router\RouteCollection as ObjectPortal3;
use Pinoox\Component\Router\Router as ObjectPortal2;
use Pinoox\Component\Source\Portal;
use Pinoox\Component\Store\Config\ConfigInterface as ObjectPortal5;
use Pinoox\Portal\Lang;
use Symfony\Component\Routing\RequestContext;

/**
 * @method static string|null package()
 * @method static string|null pathRoute()
 * @method static AppLayer current()
 * @method static App setLayer(\Pinoox\Component\Package\AppLayer $appLayer)
 * @method static mixed meeting(string $packageName, \Closure $closure, string $path = '')
 * @method static bool exists(string $packageName)
 * @method static bool stable(string $packageName)
 * @method static mixed get(?string $value = NULL)
 * @method static \Pinoox\Component\Store\Config\ConfigInterface|null set(string $key, mixed $value)
 * @method static \Pinoox\Component\Store\Config\ConfigInterface|null add(string $key, mixed $value)
 * @method static \Pinoox\Component\Store\Config\ConfigInterface|null save()
 * @method static ObjectPortal1 manager()
 * @method static ObjectPortal5 config()
 * @method static ObjectPortal8 lang()
 * @method static string path(string $path = '')
 * @method static ObjectPortal2 router()
 * @method static ObjectPortal3 routeCollection()
 * @method static \Symfony\Component\Routing\Matcher\RequestMatcherInterface|\Symfony\Component\Routing\Matcher\UrlMatcherInterface getUrlMatcher(?\Symfony\Component\Routing\RequestContext $context = NULL)
 * @method static array match(string $pathinfo, ?\Pinoox\Component\Http\Request $request = NULL)
 * @method static array matchRequest(\Pinoox\Component\Http\Request|\Symfony\Component\HttpFoundation\Request $request)
 * @method static ObjectPortal4 collection()
 * @method static ObjectPortal6 getAppRouter()
 * @method static RequestContext getContext()
 * @method static App setContext(\Symfony\Component\Routing\RequestContext $context)
 * @method static ObjectPortal7 getRequest()
 * @method static App addPackage(string $packageName, string $dir)
 * @method static \Symfony\Component\Routing\RequestContext ___context()
 * @method static \Pinoox\Component\Package\AppRouter ___router()
 * @method static \Pinoox\Component\Package\App ___()
 *
 * @see \Pinoox\Component\Package\App
 */
class App extends Portal
{
	public static function __register(): void
	{
		self::__bind(RequestContext::class, 'context');

		self::__bind(\Pinoox\Component\Package\App::class)->setArguments([
		    AppRouter::__ref(),
		    AppEngine::__ref(),
		    self::__ref('context'),
		    Loader::getClassLoader(),
		]);

		self::__watch('set', function ($key, $value) {
		    if ($key === 'lang')
		        Lang::locale($value);
		});
	}

	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'app';
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
		    'setKernel'
		];
	}
}
