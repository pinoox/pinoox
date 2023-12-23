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

namespace pinoox\portal\app;

use Closure;
use Exception;
use pinoox\component\kernel\Boot;
use pinoox\component\package\AppLayer;
use pinoox\component\package\AppLayer as ObjectPortal1;
use pinoox\component\source\Portal;
use pinoox\portal\View;

/**
 * @method static string|null package()
 * @method static AppLayer current()
 * @method static string path()
 * @method static setLayer(\pinoox\component\package\AppLayer $appLayer)
 * @method static mixed meeting(string $packageName, \Closure $closure, string $path = '')
 * @method static setPackageName(string $package)
 * @method static setPath(string $path = '')
 * @method static bool exists(string $packageName)
 * @method static bool stable(string $packageName)
 * @method static mixed get(?string $value = NULL)
 * @method static \pinoox\component\store\config\ConfigInterface|null set(string $key, mixed $value)
 * @method static \pinoox\component\store\config\ConfigInterface|null add(string $key, mixed $value)
 * @method static \pinoox\component\store\config\ConfigInterface|null save()
 * @method static \pinoox\component\package\App ___()
 *
 * @see \pinoox\component\package\App
 */
class App extends Portal
{
	public static function __register(): void
	{
		self::__bind(\pinoox\component\package\App::class)->setArguments([
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
