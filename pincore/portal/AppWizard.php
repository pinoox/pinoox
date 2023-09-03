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
use pinoox\component\wizard\Wizard as ObjectPortal1;

/**
 * @method static type(string $type)
 * @method static array|bool install()
 * @method static ObjectPortal1 open(string $path)
 * @method static bool isUpdateAvailable()
 * @method static bool isInstalled()
 * @method static ObjectPortal1 migration($val = true)
 * @method static array|null getInfo()
 * @method static mixed getErrors(bool $last = false)
 * @method static array getMeta()
 * @method static ObjectPortal1 force(bool $val = true)
 * @method static \pinoox\component\wizard\AppWizard object()
 *
 * @see \pinoox\component\wizard\AppWizard
 */
class AppWizard extends Portal
{
	public static function __register(): void
	{
		self::__bind(\pinoox\component\wizard\AppWizard::class);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'app.wizard';
	}


}
