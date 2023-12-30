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

use Iterator as ObjectPortal1;
use Pinoox\Component\Source\Portal;

/**
 * @method static \Symfony\Component\Finder\Finder create()
 * @method static \Symfony\Component\Finder\Finder directories()
 * @method static \Symfony\Component\Finder\Finder files()
 * @method static \Symfony\Component\Finder\Finder depth(array|int|string $levels)
 * @method static \Symfony\Component\Finder\Finder date(array|string $dates)
 * @method static \Symfony\Component\Finder\Finder name(array|string $patterns)
 * @method static \Symfony\Component\Finder\Finder notName(array|string $patterns)
 * @method static \Symfony\Component\Finder\Finder contains(array|string $patterns)
 * @method static \Symfony\Component\Finder\Finder notContains(array|string $patterns)
 * @method static \Symfony\Component\Finder\Finder path(array|string $patterns)
 * @method static \Symfony\Component\Finder\Finder notPath(array|string $patterns)
 * @method static \Symfony\Component\Finder\Finder size(array|int|string $sizes)
 * @method static \Symfony\Component\Finder\Finder exclude(array|string $dirs)
 * @method static \Symfony\Component\Finder\Finder ignoreDotFiles(bool $ignoreDotFiles)
 * @method static \Symfony\Component\Finder\Finder ignoreVCS(bool $ignoreVCS)
 * @method static \Symfony\Component\Finder\Finder ignoreVCSIgnored(bool $ignoreVCSIgnored)
 * @method static addVCSPattern(array|string $pattern)
 * @method static \Symfony\Component\Finder\Finder sort(\Closure $closure)
 * @method static \Symfony\Component\Finder\Finder sortByExtension()
 * @method static \Symfony\Component\Finder\Finder sortByName(bool $useNaturalSort = false)
 * @method static \Symfony\Component\Finder\Finder sortByCaseInsensitiveName(bool $useNaturalSort = false)
 * @method static \Symfony\Component\Finder\Finder sortBySize()
 * @method static \Symfony\Component\Finder\Finder sortByType()
 * @method static \Symfony\Component\Finder\Finder sortByAccessedTime()
 * @method static \Symfony\Component\Finder\Finder reverseSorting()
 * @method static \Symfony\Component\Finder\Finder sortByChangedTime()
 * @method static \Symfony\Component\Finder\Finder sortByModifiedTime()
 * @method static \Symfony\Component\Finder\Finder filter(\Closure $closure)
 * @method static \Symfony\Component\Finder\Finder followLinks()
 * @method static \Symfony\Component\Finder\Finder ignoreUnreadableDirs(bool $ignore = true)
 * @method static \Symfony\Component\Finder\Finder in(array|string $dirs)
 * @method static ObjectPortal1 getIterator()
 * @method static \Symfony\Component\Finder\Finder append(iterable $iterator)
 * @method static bool hasResults()
 * @method static int count()
 * @method static \Symfony\Component\Finder\Finder ___()
 *
 * @see \Symfony\Component\Finder\Finder
 */
class Finder extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Symfony\Component\Finder\Finder::class);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'finder';
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
