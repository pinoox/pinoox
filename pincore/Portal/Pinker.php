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

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Package\Reference\ReferenceInterface;
use Pinoox\Component\Source\Portal;
use Pinoox\Component\Store\Baker\Pinker as ObjectPortal1;
use Pinoox\Support\SystemConfig;

/**
 * @method static \Pinoox\Component\Store\Baker\Pinker create(string $mainFile = '', string $bakedFile = '', ?Pinoox\Component\Store\Baker\FileHandlerInterface $fileHandler = NULL)
 * @method static array build($data, array $info = [])
 * @method static \Pinoox\Component\Store\Baker\Pinker ___()
 *
 * @see \Pinoox\Component\Store\Baker\Pinker
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
		$mainFilePath = self::ds($path . '/' . $file);
		$mainFile = $mainFilePath;
		$mainFile = is_file($mainFile) ? $mainFile : '';

		$bakedFile = self::bakedFileFromSource($mainFilePath);

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
		$mainFilePath = self::ds(Path::resolve($fileName, 'pincore'));
		$mainFile = is_file($mainFilePath) ? $mainFilePath : '';
		$bakedFile = self::bakedFileFromSource($mainFilePath);
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
		$mainFile = self::ds($basePath . $file);
		if (!self::isAbsolutePath($mainFile) && is_string(Loader::getBasePath())) {
			$mainFile = self::ds(Loader::getBasePath() . '/' . $mainFile);
		}

		return self::create(
		    $mainFile,
		    self::bakedFileFromSource($mainFile),
		);
	}

	public static function bakedFileFromSource(string $sourceFile): string
	{
		$sourceFile = self::ds($sourceFile);
		$basePath = self::rootPath();
		$relative = $sourceFile;
		$corePath = defined('PINOOX_CORE_PATH') ? rtrim(self::ds(\PINOOX_CORE_PATH), '/') : $basePath . '/pincore';

		if (!empty($corePath) && str_starts_with($sourceFile, $corePath . '/config/')) {
			$relative = 'config' . substr($sourceFile, strlen($corePath . '/config'));
		} elseif (!empty($corePath) && ($sourceFile === $corePath || str_starts_with($sourceFile, $corePath . '/'))) {
			$relative = 'pincore' . substr($sourceFile, strlen($corePath));
		} elseif (!empty($basePath) && str_starts_with($sourceFile, $basePath . '/')) {
			$relative = substr($sourceFile, strlen($basePath) + 1);
		}

		return self::ds(SystemConfig::path('pinker') . '/' . ltrim($relative, '/'));
	}

	public static function rootPath(): string
	{
		return rtrim(self::ds((string)Loader::getBasePath()), '/');
	}

	private static function isAbsolutePath(string $path): bool
	{
		return preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/');
	}

	public static function ds(string $path): string
	{
		return str_replace('\\', '/', $path);
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

