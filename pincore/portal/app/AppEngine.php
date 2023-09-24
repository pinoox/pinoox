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

namespace pinoox\portal\app;

use pinoox\component\router\Router as ObjectPortal2;
use pinoox\component\source\Portal;
use pinoox\component\store\config\Config as ObjectPortal1;
use pinoox\component\store\config\strategy\FileConfigStrategy;
use pinoox\portal\Config;
use pinoox\portal\Pinker;

/**
 * @method static ObjectPortal2 routes(\pinoox\component\package\reference\ReferenceInterface|string $packageName)
 * @method static ObjectPortal1 config(\pinoox\component\package\reference\ReferenceInterface|string $packageName)
 * @method static bool exists(\pinoox\component\package\reference\ReferenceInterface|string $packageName)
 * @method static AppEngine add($packageName, $path)
 * @method static bool supports(\pinoox\component\package\reference\ReferenceInterface|string $packageName)
 * @method static string path(\pinoox\component\package\reference\ReferenceInterface|string $packageName)
 * @method static \pinoox\component\package\engine\AppEngine ___()
 *
 * @see \pinoox\component\package\engine\AppEngine
 */
class AppEngine extends Portal
{
	const file = 'app.php';

	public static function __register(): void
	{
//        $path = PINOOX_PATH.'pincore';
//        $file = 'config'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'router.config.php';
//        $fileStrategy = new FileConfigStrategy(Pinker::folder($path,$file));
//        $config = Config::create($fileStrategy);
//
		$pathApps = PINOOX_PATH . 'apps';
		$pathConfig = 'config' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'source.config.php';
		$appConfig = Pinker::path($pathConfig,PINOOX_PATH . 'pincore');
		self::__bind(\pinoox\component\package\engine\AppEngine::class)
		    ->setArguments([
		        $pathApps,
		        self::file,
		        Pinker::folder,
                $appConfig->pickup(),
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
