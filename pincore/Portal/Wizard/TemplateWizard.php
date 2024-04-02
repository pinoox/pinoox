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

namespace Pinoox\Portal\Wizard;

use Pinoox\Component\Source\Portal;
use Pinoox\Component\Wizard\Wizard as ObjectPortal1;
use Pinoox\Portal\App\AppEngine;

/**
 * @method static \Pinoox\Component\Wizard\TemplateWizard open(string $path)
 * @method static array install()
 * @method static array|null getInfo()
 * @method static bool isUpdateAvailable()
 * @method static mixed getErrors(bool $last = false)
 * @method static array getMeta()
 * @method static TemplateWizard type($type)
 * @method static ObjectPortal1 force(bool $val = true)
 * @method static deleteTemp()
 * @method static \Pinoox\Component\Wizard\TemplateWizard ___()
 *
 * @see \Pinoox\Component\Wizard\TemplateWizard
 */
class TemplateWizard extends Portal
{
	private const tmpPathRoot = 'wizard_tmp';

	public static function __register(): void
	{
		$tmpPath = path('pinker/' . self::tmpPathRoot);
		self::__bind(\Pinoox\Component\Wizard\TemplateWizard::class)->setArguments([
		    $tmpPath,
		    AppEngine::__ref()
		]);
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
			'type'
		];
	}
}
