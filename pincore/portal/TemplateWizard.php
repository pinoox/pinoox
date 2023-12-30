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
use Pinoox\Component\Wizard\Wizard as ObjectPortal1;

/**
 * @method static type(string $type)
 * @method static ObjectPortal1 open(string $path)
 * @method static array install()
 * @method static ?array getInfo()
 * @method static bool isUpdateAvailable()
 * @method static mixed getErrors($last = false)
 * @method static array getMeta()
 * @method static \Pinoox\Component\Wizard\TemplateWizard ___()
 *
 * @see \Pinoox\Component\Wizard\TemplateWizard
 */
class TemplateWizard extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\Wizard\TemplateWizard::class);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'template.wizard';
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
