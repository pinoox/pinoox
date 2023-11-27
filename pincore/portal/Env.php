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

use pinoox\component\source\Portal;

/**
 * @method static mixed get(?string $key = NULL, mixed $default = NULL)
 * @method static Env set(string $key, mixed $value)
 * @method static Env remove(string $key)
 * @method static restore()
 * @method static \pinoox\component\Helpers\Env ___()
 *
 * @see \pinoox\component\Helpers\Env
 */
class Env extends Portal
{
	public static function __register(): void
	{
		self::__bind(\pinoox\component\Helpers\Env::class);
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
