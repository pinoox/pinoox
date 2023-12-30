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
use Pinoox\Component\Source\Portal;

/**
 * @method static string|null package()
 * @method static AppLayer current()
 * @method static string path()
 * @method static setLayer(\Pinoox\Component\Package\AppLayer $appLayer)
 * @method static mixed meeting(string $packageName, \Closure $closure, string $path = '')
 * @method static setPackageName(string $package)
 * @method static setPath(string $path = '')
 * @method static bool exists(string $packageName)
 * @method static bool stable(string $packageName)
 * @method static mixed get(?string $value = NULL)
 * @method static \Pinoox\Component\Store\Config\ConfigInterface|null set(string $key, mixed $value)
 * @method static \Pinoox\Component\Store\Config\ConfigInterface|null add(string $key, mixed $value)
 * @method static \Pinoox\Component\Store\Config\ConfigInterface|null save()
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
