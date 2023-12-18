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

use pinoox\component\kernel\Loader;
use pinoox\component\source\Portal;

/**
 * @method static register()
 * @method static \pinoox\component\Dumper\Dumper ___()
 *
 * @see \pinoox\component\Dumper\Dumper
 */
class Dumper extends Portal
{
	public static function __register(): void
	{
		self::__bind(\pinoox\component\Dumper\Dumper::class)->setArguments([
            Loader::basePath()
		]);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'dumper';
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
