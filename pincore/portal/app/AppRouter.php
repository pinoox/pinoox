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

use pinoox\component\package\AppLayer as ObjectPortal1;
use pinoox\component\source\Portal;
use pinoox\component\store\config\strategy\FileConfigStrategy;
use pinoox\portal\Config;
use pinoox\portal\Pinker;

/**
 * @method static setDefault($packageName)
 * @method static ObjectPortal1 find(?string $url = NULL)
 * @method static bool stable(string $packageName)
 * @method static set($url, $packageName)
 * @method static delete(string $url)
 * @method static deletePackage(string $packageName)
 * @method static mixed get(?string $value = NULL)
 * @method static setData(mixed $data = NULL)
 * @method static array|null getPackage(string $packageName)
 * @method static bool exists(string $url)
 * @method static bool existsPackage(string $packageName)
 * @method static \pinoox\component\package\AppRouter ___()
 *
 * @see \pinoox\component\package\AppRouter
 */
class AppRouter extends Portal
{
	public static function __register(): void
	{
		$path = PINOOX_PATH.'pincore';
		$file = 'config'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'router.config.php';
        $fileStrategy = new FileConfigStrategy(Pinker::folder($path,$file));
		$config = Config::create($fileStrategy);

		self::__bind(\pinoox\component\package\AppRouter::class)->setArguments([
		    $config,
		    AppEngine::__ref(),
		]);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'app.router';
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
