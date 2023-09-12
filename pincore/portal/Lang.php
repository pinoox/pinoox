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

use pinoox\component\lang\source\FileLangSource;
use pinoox\component\package\reference\PathReference;
use pinoox\component\package\reference\ReferenceInterface;
use pinoox\component\source\Portal;

/**
 * @method static \pinoox\component\lang\Lang create(\pinoox\component\lang\source\LangSource $source)
 * @method static \pinoox\component\lang\Lang ___()
 *
 * @see \pinoox\component\lang\Lang
 */
class Lang extends Portal
{
	const locale = 'en';
	const folder = 'lang';
	const ext = 'lang.php';

	private static array $tmp = [];


	public static function __register(): void
	{
		self::__bind(\pinoox\component\lang\Lang::class);
	}


	/**
	 * Set file for pinoox baker
	 *
	 * @param string|ReferenceInterface $fileName
	 * @return \pinoox\component\lang\Lang
	 */
	public static function name(string|ReferenceInterface $fileName): \pinoox\component\lang\Lang
	{
		return self::initFileConfig($fileName);
	}


	private static function initFileConfig(string $fileName): \pinoox\component\lang\Lang
	{
		if (empty(self::$tmp[$fileName])) {
		    $reference = Path::reference($fileName);
		    $reference = PathReference::create(
		        $reference->getPackageName(),
		        'lang',
		    );
		    $path = Path::createPath($reference,'pincore');
		    self::$tmp[$fileName] = self::create(new FileLangSource($path));

		    dd(self::$tmp[$fileName]);
		}

		//return (self::$tmp[$fileName])->get($reference->getPath());
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'lang';
	}


	/**
	 * Get include method names .
	 * @return string[]
	 */
	public static function __include(): array
	{
		return ['name','create'];
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
