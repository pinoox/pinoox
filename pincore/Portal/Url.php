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

use Pinoox\Component\Http\Request as ObjectPortal1;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\App\AppRouter;

/**
 * @method static string host()
 * @method static string httpHost()
 * @method static string scheme()
 * @method static string port()
 * @method static string scriptName()
 * @method static string method()
 * @method static string realMethod()
 * @method static string clientIp()
 * @method static string|null userAgent()
 * @method static array clientIps()
 * @method static string base()
 * @method static string params()
 * @method static string route($name, $parameters = [], bool $isFullBase = true)
 * @method static array parameters()
 * @method static string site(bool $isFullBase = true)
 * @method static string app(bool $isFullBase = true)
 * @method static string get(string $path = '', bool $isFullBase = true)
 * @method static string loc(string $path = '', bool $isFullBase = true)
 * @method static string path(string $path = '', bool $isFullBase = true)
 * @method static check($link, $default = NULL)
 * @method static bool existsFile($link)
 * @method static ObjectPortal1 request()
 * @method static referer()
 * @method static current()
 * @method static array getAppUrls(string $packageName)
 * @method static \Pinoox\Component\Path\Url ___()
 *
 * @see \Pinoox\Component\Path\Url
 */
class Url extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\Path\Url::class)
		    ->setArgument('app', App::__ref())
		    ->setArgument('request', AppProvider::getRequest())
		    ->setArgument('appRouter', AppRouter::__ref())
		    ->setArgument('basePath', Loader::getBasePath());
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'url';
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
