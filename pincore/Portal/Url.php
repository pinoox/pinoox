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

use Pinoox\Component\Helpers\Url as ObjectPortal1;
use Pinoox\Component\Path\Parser\UrlParser;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\AppEngine;

/**
 * @method static ObjectPortal1 set($key, $value)
 * @method static string|null app(?string $packageName = NULL)
 * @method static string replaceToSlash(string $url, array|string $search = array (  0 => '\\',  1 => '>',))
 * @method static string prefix(\Pinoox\Component\Path\Reference\ReferenceInterface|string $url, string $prefix)
 * @method static string get(\Pinoox\Component\Path\Reference\ReferenceInterface|string $url = '')
 * @method static string prefixName(\Pinoox\Component\Path\Reference\ReferenceInterface|string $url, string $prefix)
 * @method static \Pinoox\Component\Path\Parser\UrlParser ___parser()
 * @method static \Pinoox\Component\Helpers\Url ___()
 *
 * @see \Pinoox\Component\Helpers\Url
 */
class Url extends Portal
{
	public static function __register(): void
	{
		self::__bind(UrlParser::class, 'parser')
		    ->setArguments(['com_pinoox_test']);

		self::__bind(ObjectPortal1::class)
		    ->setArgument('parser', self::__ref('parser'))
		    ->setArgument('appEngine', AppEngine::__instance())
		    ->setArgument('baseUrl', null);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'url';
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
