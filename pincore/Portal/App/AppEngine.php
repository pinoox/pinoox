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
use Pinoox\Component\Lang\Lang as ObjectPortal4;
use Pinoox\Component\Package\AppManager;
use Pinoox\Component\Path\Manager\ManagerInterface;
use Pinoox\Component\Router\Router as ObjectPortal2;
use Pinoox\Component\Source\Portal;
use Pinoox\Component\Store\Config\Config as ObjectPortal1;
use Pinoox\Component\Store\Config\ConfigInterface as ObjectPortal3;
use Pinoox\Portal\Pinker;

/**
 * @method static bool stable(\Pinoox\Component\Path\Reference\ReferenceInterface|string $packageName)
 * @method static array getAllRouters(\Pinoox\Component\Path\Reference\ReferenceInterface|string $packageName)
 * @method static ObjectPortal2 router(\Pinoox\Component\Path\Reference\ReferenceInterface|string $packageName, string $path = '/')
 * @method static AppManager manager(\Pinoox\Component\Path\Reference\ReferenceInterface|string $packageName)
 * @method static ObjectPortal4 lang(\Pinoox\Component\Path\Reference\ReferenceInterface|string $packageName)
 * @method static ObjectPortal3 config(\Pinoox\Component\Path\Reference\ReferenceInterface|string $packageName)
 * @method static bool exists(\Pinoox\Component\Path\Reference\ReferenceInterface|string $packageName)
 * @method static AppEngine add(string $packageName, string $path)
 * @method static string path(\Pinoox\Component\Path\Reference\ReferenceInterface|string $packageName, string $path = '')
 * @method static bool supports(\Pinoox\Component\Path\Reference\ReferenceInterface|string $packageName)
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
		//        $path = PINOOX_PATH.'pincore';
		//        $file = 'config/app/router.config.php';
		//        $fileStrategy = new FileConfigStrategy(Pinker::folder($path,$file));
		//        $config = Config::create($fileStrategy);
		//
		$pathApps = Loader::getBasePath() . '/apps';
		$pathConfig = 'config/app/source.config.php';
		$appConfig = Pinker::path($pathConfig,Loader::getBasePath() . '/pincore');
		$defaultData = $appConfig->pickup()?? [];
		self::__bind(\Pinoox\Component\Package\Engine\AppEngine::class)
		    ->setArguments([
		        $pathApps,
		        self::file,
		        Pinker::folder,
		        $defaultData,
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
