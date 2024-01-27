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

/**
 * @method static register()
 * @method static \Pinoox\Component\Dumper\Dumper ___()
 *
 * @see \Pinoox\Component\Dumper\Dumper
 */
class Dumper extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\Dumper\Dumper::class)->setArguments([
           Loader::getBasePath()
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
