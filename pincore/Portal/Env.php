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

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\App;

/**
 * @method static mixed get(?string $key = NULL, mixed $default = NULL)
 * @method static Env set(string $key, mixed $value)
 * @method static Env remove(string $key)
 * @method static Env restore()
 * @method static Env register()
 * @method static \Pinoox\Component\Helpers\Env ___()
 *
 * @see \Pinoox\Component\Helpers\Env
 */
class Env extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\Helpers\Env::class)->setArguments([
            Loader::getBasePath(),
            App::path()
		]);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'env';
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
		    'set'
		];
	}
}
