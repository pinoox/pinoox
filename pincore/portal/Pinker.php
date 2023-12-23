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

use pinoox\component\package\reference\PathReference;
use pinoox\component\package\reference\ReferenceInterface;
use pinoox\component\source\Portal;
use pinoox\component\store\baker\Pinker as ObjectPortal1;

/**
 * @method static \pinoox\component\store\baker\Pinker create(string $mainFile = '', string $bakedFile = '', ?\pinoox\component\store\baker\FileHandlerInterface $fileHandler = NULL)
 * @method static array build($data, array $info = [])
 * @method static \pinoox\component\store\baker\Pinker ___()
 *
 * @see \pinoox\component\store\baker\Pinker
 */
class Pinker extends Portal
{
	const folder = 'pinker';

	public static function __register(): void
	{
		self::__bind(ObjectPortal1::class);
	}


	public static function folder(string $path, string $file): ObjectPortal1
	{
		$mainFile = $path . '/' . $file;
        $mainFile = is_file($mainFile) ? $mainFile : '';

		$bakedFile = $path . '/' . self::folder . '/' . $file;

		return self::create($mainFile, $bakedFile);
	}


	/**
	 * get pinker by file
	 *
	 * @param string|ReferenceInterface $fileName
	 * @return ObjectPortal1
	 */
	public static function file(string|ReferenceInterface $fileName): ObjectPortal1
	{
		$mainFile = Path::createPath($fileName, 'pincore');
		$mainFile = is_file($mainFile) ? $mainFile : '';
		$bakedFileName = !is_string($fileName) ? $fileName->getPath() : $fileName;
		$bakedFileName = self::folder . '/' . $bakedFileName;

		if (!is_string($fileName)) {
		    $fileName = PathReference::create($fileName->getPackageName(), $bakedFileName);
		}

		$bakedFile = Path::createPath($fileName, 'pincore');
		return self::create($mainFile, $bakedFile);
	}


	/**
	 * get pinker by path
	 *
	 * @param string $file
	 * @param string|null $basePath
	 * @return ObjectPortal1
	 */
	public static function path(string $file, ?string $basePath = null): ObjectPortal1
	{
		$basePath = !empty($basePath) ? $basePath . '/' : '';
		return self::create(
		    self::ds($basePath . $file),
		    self::ds($basePath . Pinker::folder . '/' . $file),
		);
	}


	public static function ds(string $path): string
	{
		return str_replace(['/', '\\'], '/', $path);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'pinker';
	}


	/**
	 * Get include method names .
	 * @return string[]
	 */
	public static function __include(): array
	{
		return ['file', 'create','build'];
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
