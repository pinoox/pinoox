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

use Pinoox\Component\Lang\ource\FileLangSource;
use Pinoox\Component\Path\Reference\PathReference;
use Pinoox\Component\Path\Reference\ReferenceInterface;
use Pinoox\Component\Source\Portal;

/**
 * @method static \Pinoox\Component\Lang\Lang create(\Pinoox\Component\Lang\Source\LangSource $source)
 * @method static \Pinoox\Component\Lang\Lang ___()
 *
 * @see \Pinoox\Component\Lang\Lang
 */
class Lang extends Portal
{
	const locale = 'en';
	const folder = 'lang';
	const ext = 'lang.php';

	private static array $tmp = [];


	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\Lang\Lang::class);
	}


	/**
	 * Set file for pinoox baker
	 *
	 * @param string|ReferenceInterface $fileName
	 * @return \Pinoox\Component\Lang\Lang
	 */
	public static function name(string|ReferenceInterface $fileName): \Pinoox\Component\Lang\Lang
	{
		return self::initFileConfig($fileName);
	}


	private static function initFileConfig(string $fileName): \Pinoox\Component\Lang\Lang
	{
		if (empty(self::$tmp[$fileName])) {
		    $reference = Path::reference($fileName);
		    $reference = PathReference::create(
		        $reference->getPackageName(),
		        'lang',
		    );
		    $path = Path::createPath($reference,'pincore');
		    self::$tmp[$fileName] = self::create(new FileLangSource($path));
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
