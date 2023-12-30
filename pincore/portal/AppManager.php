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


use Pinoox\Component\Source\Portal;

/**
 * @method static array getApps()
 * @method static mixed getApp(string $package)
 * @method static \Pinoox\Component\Manager\AppManager ___()
 *
 * @see \Pinoox\Component\Manager\AppManager
 */
class AppManager extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\Manager\AppManager::class);
	}

	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'app.manager';
	}

}
