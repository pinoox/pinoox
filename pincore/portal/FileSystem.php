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
 * @method static copy(string $originFile, string $targetFile, bool $overwriteNewerFiles = false)
 * @method static mkdir(iterable|string $dirs, int $mode = 511)
 * @method static bool exists(iterable|string $files)
 * @method static touch(iterable|string $files, ?int $time = NULL, ?int $atime = NULL)
 * @method static remove(iterable|string $files)
 * @method static chmod(iterable|string $files, int $mode, int $umask = 0, bool $recursive = false)
 * @method static chown(iterable|string $files, int|string $user, bool $recursive = false)
 * @method static chgrp(iterable|string $files, int|string $group, bool $recursive = false)
 * @method static rename(string $origin, string $target, bool $overwrite = false)
 * @method static symlink(string $originDir, string $targetDir, bool $copyOnWindows = false)
 * @method static hardlink(string $originFile, iterable|string $targetFiles)
 * @method static string|null readlink(string $path, bool $canonicalize = false)
 * @method static string makePathRelative(string $endPath, string $startPath)
 * @method static mirror(string $originDir, string $targetDir, ?Traversable $iterator = NULL, array $options = [])
 * @method static bool isAbsolutePath(string $file)
 * @method static string tempnam(string $dir, string $prefix, string $suffix = '')
 * @method static dumpFile(string $filename, $content)
 * @method static appendToFile(string $filename, $content)
 * @method static FileSystem handleError(int $type, string $msg)
 * @method static \Symfony\Component\Filesystem\Filesystem ___()
 *
 * @see \Symfony\Component\Filesystem\Filesystem
 */
class FileSystem extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Symfony\Component\Filesystem\Filesystem::class);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'file.system';
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
			'handleError'
		];
	}
}
