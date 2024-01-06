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

namespace Pinoox\Portal\Kernel;

use Pinoox\Component\Source\Portal;

/**
 * @method static Terminal run()
 * @method static addCommand()
 * @method static \Pinoox\Component\Kernel\Terminal ___()
 *
 * @see \Pinoox\Component\Kernel\Terminal
 */
class Terminal extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\Kernel\Terminal::class);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'terminal';
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
			'run'
		];
	}
}
