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
use Pinoox\Component\Path\Url as UrlComponent;
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
 * @method static array parameters()
 * @method static string origin(bool $absolute = true)
 * @method static string sitePath()
 * @method static string routeSegment(?string $package = NULL)
 * @method static string appPath(?string $package = NULL)
 * @method static \Pinoox\Component\Path\UrlAccessor accessor(?string $package = NULL)
 * @method static \Pinoox\Component\Path\ThemeAccessor themeAccessor(?string $name = NULL, ?string $package = NULL)
 * @method static \Pinoox\Component\Path\AppAccessor appAccessor(?string $package = NULL)
 * @method static string forApp(?string $package = NULL)
 * @method static string link(string $link = '', string $scope = self::SCOPE_APP, string $mode = self::MODE_AUTO)
 * @method static bool isRoutePath(string $link)
 * @method static string to(string $path = '', string $scope = self::SCOPE_APP)
 * @method static string asset(string $path = '', ?string $package = NULL)
 * @method static string assetPath(string $path = '', ?string $package = NULL)
 * @method static string normalizeAppPublicPath(string $path, ?string $package = NULL)
 * @method static string fromPath(string $filesystemPath)
 * @method static string reference(\Pinoox\Component\Package\Reference\ReferenceInterface|string $ref, ?string $package = NULL)
 * @method static string route(string $name, array $parameters = [], bool $absolute = true)
 * @method static string action(string $actionReference, array $parameters = [], bool $absolute = true)
 * @method static array appUrls(string $package)
 * @method static string|null appUrl(string $package)
 * @method static check(?string $link, ?string $default = NULL)
 * @method static bool existsFile(?string $link)
 * @method static ObjectPortal1 request()
 * @method static string pathWithoutBase()
 * @method static string|null referer()
 * @method static string current()
 * @method static bool isSecure()
 * @method static bool isQueryRoute()
 * @method static string queryRoute(string $path = '', bool $absolute = true)
 * @method static string queryRouteForApp(string $path, ?string $package = NULL, bool $absolute = true)
 * @method static \Pinoox\Component\Path\Url ___()
 *
 * @see \Pinoox\Component\Path\Url
 */
class Url extends Portal
{

	public const SCOPE_APP = UrlComponent::SCOPE_APP;

	public const SCOPE_SITE = UrlComponent::SCOPE_SITE;

	public const SCOPE_RELATIVE = UrlComponent::SCOPE_RELATIVE;

	public const APP = UrlComponent::SCOPE_APP;

	public const SITE = UrlComponent::SCOPE_SITE;

	public const RELATIVE = UrlComponent::SCOPE_RELATIVE;

	public const APP_PATH = UrlComponent::SCOPE_APP_PATH;

	public const MODE_AUTO = UrlComponent::MODE_AUTO;

	public const MODE_CLEAN = UrlComponent::MODE_CLEAN;

	public const MODE_QUERY = UrlComponent::MODE_QUERY;

	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\Path\Url::class)
		    ->setArgument('app', App::__ref())
		    ->setArgument('request', AppProvider::getRequest())
		    ->setArgument('appRouter', AppRouter::__ref())
		    ->setArgument('path', Path::__ref())
		    ->setArgument('basePath', Loader::getBasePath());
	}

	public static function __name(): string
	{
		return 'url';
	}

	/**
	 * @return string[]
	 */
	public static function __exclude(): array
	{
		return [];
	}

	/**
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [];
	}

	public static function themeAccessor(?string $name = null, ?string $package = null): \Pinoox\Component\Path\ThemeAccessor
	{
		return self::___()->themeAccessor($name, $package);
	}

	public static function appAccessor(?string $package = null): \Pinoox\Component\Path\AppAccessor
	{
		return self::___()->appAccessor($package);
	}
}

