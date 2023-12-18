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

use pinoox\component\Helpers\Path as ObjectPortal1;
use pinoox\component\kernel\Loader;
use pinoox\component\package\parser\PathParser;
use pinoox\component\package\reference\PathReference;
use pinoox\component\package\reference\ReferenceInterface;
use pinoox\component\package\reference\ReferenceInterface as ObjectPortal2;
use pinoox\component\source\Portal;
use pinoox\portal\app\App;
use pinoox\portal\app\AppEngine;

/**
 * @method static string|null app(?string $packageName = NULL)
 * @method static string get(\pinoox\component\package\reference\ReferenceInterface|string $path = '')
 * @method static ObjectPortal1 set($key, $value)
 * @method static string ds(string $path)
 * @method static ReferenceInterface parse(string $name)
 * @method static string prefixName(\pinoox\component\package\reference\ReferenceInterface|string $path, string $prefix)
 * @method static string prefix(\pinoox\component\package\reference\ReferenceInterface|string $path, string $prefix)
 * @method static ReferenceInterface prefixReference(\pinoox\component\package\reference\ReferenceInterface|string $path, string $prefix)
 * @method static ReferenceInterface reference(\pinoox\component\package\reference\ReferenceInterface|string $path)
 * @method static \pinoox\component\package\parser\PathParser ___parser()
 * @method static \pinoox\component\Helpers\Path ___()
 *
 * @see \pinoox\component\Helpers\Path
 */
class Path extends Portal
{
	public static function __register(): void
	{
		parent::__bind(PathParser::class, 'parser')
		    ->setArguments([App::package()]);

		parent::__bind(ObjectPortal1::class)
		    ->setArgument('parser', self::__ref('parser'))
		    ->setArgument('appEngine', AppEngine::__instance())
		    ->setArgument('basePath', Loader::basePath());
	}


	public static function createPath(string|ObjectPortal2 $fileName, string $default = 'pincore'): string
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
