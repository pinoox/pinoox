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

use Pinoox\Component\Package\AppLayer;
use Pinoox\Component\Package\AppManager as ObjectPortal1;
use Pinoox\Component\Router\Collection as ObjectPortal4;
use Pinoox\Component\Router\RouteCollection as ObjectPortal3;
use Pinoox\Component\Router\Router as ObjectPortal2;
use Pinoox\Component\Source\Portal;

/**
 * @method static string|null package()
 * @method static string|null route()
 * @method static AppLayer current()
 * @method static setLayer(\Pinoox\Component\Package\AppLayer $appLayer)
 * @method static mixed meeting(string $packageName, \Closure $closure, string $path = '')
 * @method static bool exists(string $packageName)
 * @method static bool stable(string $packageName)
 * @method static mixed get(?string $value = NULL)
 * @method static \Pinoox\Component\Store\Config\ConfigInterface|null set(string $key, mixed $value)
 * @method static \Pinoox\Component\Store\Config\ConfigInterface|null add(string $key, mixed $value)
 * @method static \Pinoox\Component\Store\Config\ConfigInterface|null save()
 * @method static ObjectPortal1 manager()
 * @method static string path(string $path = '')
 * @method static ObjectPortal2 router()
 * @method static ObjectPortal3 routeCollection()
 * @method static ObjectPortal4 collection()
 * @method static \Pinoox\Component\Package\App ___()
 *
 * @see \Pinoox\Component\Package\App
 */
class App extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\Package\App::class)->setArguments([
		    AppRouter::find(),
		    AppEngine::__ref(),
		]);
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
		return [];
	}
}
