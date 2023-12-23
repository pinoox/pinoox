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

use pinoox\component\Path\Path as ObjectPortal1;
use pinoox\component\Path\parser\PathParser;
use pinoox\component\Path\reference\PathReference;
use pinoox\component\Path\reference\ReferenceInterface;
use pinoox\component\kernel\Loader;
use pinoox\component\source\Portal;
use pinoox\portal\app\App;
use pinoox\portal\app\AppEngine;

/**
 * @method static string|null app(?string $packageName = NULL)
 * @method static string get(\pinoox\component\Path\reference\ReferenceInterface|string $path = '', string $package = '')
 * @method static \pinoox\component\Path\Path set($key, $value)
 * @method static ReferenceInterface parse(string $name)
 * @method static string prefixName(\pinoox\component\Path\reference\ReferenceInterface|string $path, string $prefix)
 * @method static string prefix(\pinoox\component\Path\reference\ReferenceInterface|string $path, string $prefix)
 * @method static ReferenceInterface prefixReference(\pinoox\component\Path\reference\ReferenceInterface|string $path, string $prefix)
 * @method static ReferenceInterface reference(\pinoox\component\Path\reference\ReferenceInterface|string $path)
 * @method static \pinoox\component\Path\parser\PathParser ___parser()
 * @method static \pinoox\component\Path\Path ___()
 *
 * @see \pinoox\component\Path\Path
 */
class Path extends Portal
{
	public static function __register(): void
	{
		self::__bind(PathParser::class, 'parser');

		self::__bind(ObjectPortal1::class)
		    ->setArgument('basePath', Loader::basePath())
		    ->setArgument('parser', self::__ref('parser'))
		    ->setArgument('appEngine', AppEngine::__instance())
		    ->setArgument('appLayer', App::current());
	}


	public static function createPath(string|ReferenceInterface $fileName, string $default = 'pincore'): string
	{
		$reference = self::reference($fileName);
		$pathMain = $reference->getPackageName() === '~' ? $default . '/' . $reference->getPath() : $reference->getPath();

		$reference = PathReference::create(
		    $reference->getPackageName(),
		    $pathMain,
		);

		return self::get($reference);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'path';
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
