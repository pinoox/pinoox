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
 * @method static array getApps()
 * @method static mixed getApp(string $package)
 * @method static \pinoox\component\manager\AppManager ___()
 *
 * @see \pinoox\component\manager\AppManager
 */
class AppManager extends Portal
{
	public static function __register(): void
	{
		self::__bind(\pinoox\component\manager\AppManager::class);
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
