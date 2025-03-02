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
use Pinoox\Component\Package\Parser\NameParser;
use Pinoox\Component\Path\Path as ObjectPortal1;
use Pinoox\Component\Package\Reference\NameReference;
use Pinoox\Component\Package\Reference\ReferenceInterface;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;

/**
 * @method static string|null app(?string $packageName = NULL)
 * @method static string get(\Pinoox\Component\Package\Reference\ReferenceInterface|string $path = '', string $package = '')
 * @method static string params(\Pinoox\Component\Package\Reference\ReferenceInterface|string $path = '', string $package = '')
 * @method static \Pinoox\Component\Path\Path set($key, $value)
 * @method static ReferenceInterface parse(string $name)
 * @method static string prefixName(\Pinoox\Component\Package\Reference\ReferenceInterface|string $path, string $prefix)
 * @method static string prefix(\Pinoox\Component\Package\Reference\ReferenceInterface|string $path, string $prefix)
 * @method static ReferenceInterface prefixReference(\Pinoox\Component\Package\Reference\ReferenceInterface|string $path, string $prefix)
 * @method static ReferenceInterface reference(\Pinoox\Component\Package\Reference\ReferenceInterface|string $path)
 * @method static \Pinoox\Component\Path\Path ___()
 *
 * @see \Pinoox\Component\Path\Path
 */
class Path extends Portal
{
	public static function __register(): void
	{
		self::__bind(NameParser::class, 'parser');

		self::__bind(ObjectPortal1::class)
		    ->setArgument('basePath', Loader::getBasePath())
		    ->setArgument('parser', self::__ref('parser'))
		    ->setArgument('appEngine', AppEngine::__instance())
		    ->setArgument('package', App::package());


	}


	public static function __app(): ?string
	{
		return App::package();
	}

	public static function createPath(string|ReferenceInterface $fileName, string $default = 'pincore'): string
	{
		$reference = self::reference($fileName);
		$pathMain = $reference->getPackageName() === '~' ? $default . '/' . $reference->getValue() : $reference->getValue();

		$reference = NameReference::create(
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
