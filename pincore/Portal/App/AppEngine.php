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

namespace Pinoox\Portal\App;

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Package\AppManager;
use Pinoox\Component\Router\Router as ObjectPortal2;
use Pinoox\Component\Source\Portal;
use Pinoox\Component\Store\Config\ConfigInterface as ObjectPortal3;
use Pinoox\Component\Translator\Translator as ObjectPortal1;
use Pinoox\Portal\Pinker;

/**
 * @method static array getDefaultData()
 * @method static bool stable(\Pinoox\Component\Package\Reference\ReferenceInterface|string $packageName)
 * @method static array getAllRouters(\Pinoox\Component\Package\Reference\ReferenceInterface|string $packageName)
 * @method static ObjectPortal2 router(\Pinoox\Component\Package\Reference\ReferenceInterface|string $packageName, string $path = '/')
 * @method static AppManager manager(\Pinoox\Component\Package\Reference\ReferenceInterface|string $packageName)
 * @method static ObjectPortal1 lang(\Pinoox\Component\Package\Reference\ReferenceInterface|string $packageName)
 * @method static ObjectPortal3 config(\Pinoox\Component\Package\Reference\ReferenceInterface|string $packageName)
 * @method static bool exists(\Pinoox\Component\Package\Reference\ReferenceInterface|string $packageName)
 * @method static AppEngine add(string $packageName, string $path)
 * @method static string path(\Pinoox\Component\Package\Reference\ReferenceInterface|string $packageName, string $path = '')
 * @method static bool supports(\Pinoox\Component\Package\Reference\ReferenceInterface|string $packageName)
 * @method static bool checkName(string $packageName)
 * @method static array all()
 * @method static \Pinoox\Component\Package\Engine\AppEngine ___()
 *
 * @see \Pinoox\Component\Package\Engine\AppEngine
 */
class AppEngine extends Portal
{
	const file = 'app.php';

	public static function __register(): void
	{
		$pathApps = Loader::getBasePath() . '/apps';
		self::__bind(\Pinoox\Component\Package\Engine\AppEngine::class)
		    ->setArguments([
		        $pathApps,
		        self::file,
		        Pinker::folder,
		    ]);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'app.engine';
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
		    'add'
		];
	}
}
