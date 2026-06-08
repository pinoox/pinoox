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

use Pinoox\Component\Http\Request;
use Pinoox\Component\Package\AppLayer as ObjectPortal1;
use Pinoox\Component\Source\Portal;
use Pinoox\Component\Store\Config\ConfigInterface as ObjectPortal2;
use Pinoox\Component\Store\Config\Strategy\FileConfigStrategy;
use Pinoox\Portal\Config;
use Pinoox\Portal\Pinker;
use Pinoox\Support\SystemApp;

/**
 * @method static setDefault($packageName)
 * @method static \Pinoox\Component\Package\AppLayer find(?string $url = NULL)
 * @method static \Pinoox\Component\Package\AppLayer|null resolved()
 * @method static string host()
 * @method static string|null subdomain()
 * @method static \Pinoox\Component\Package\Routing\DomainMatch|null domainMatch()
 * @method static bool stable(string $packageName)
 * @method static set($url, $packageName)
 * @method static delete(string $url)
 * @method static deletePackage(string $packageName)
 * @method static mixed get(?string $value = NULL, mixed $default = NULL)
 * @method static setData(mixed $data = NULL)
 * @method static array routes()
 * @method static array getByPackage(string $packageName)
 * @method static bool exists(string $url)
 * @method static bool existByPackage(string $packageName)
 * @method static Request getRequest()
 * @method static AppRouter setRequest(\Pinoox\Component\Http\Request $request)
 * @method static ObjectPortal2 config()
 * @method static \Pinoox\Component\Package\AppRouter ___()
 *
 * @see \Pinoox\Component\Package\AppRouter
 */
class AppRouter extends Portal
{
	public static function __register(): void
	{
		$file = 'config/app-router.config.php';
		$fileStrategy = new FileConfigStrategy(Pinker::folder(SystemApp::legacyCorePath(), $file));
		$config = Config::create($fileStrategy);
		self::__bind(\Pinoox\Component\Package\AppRouter::class)->setArguments([
		    $config,
		    AppEngine::__ref(),
		    Request::take()
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
		return [
			'setRequest'
		];
	}
}

