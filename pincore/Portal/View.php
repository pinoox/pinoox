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

use Pinoox\Component\Http\JsonResponse;
use Pinoox\Component\Http\Response;
use Pinoox\Component\Source\Portal;
use Pinoox\Component\Template\Reference\TemplatePathReference as ObjectPortal2;
use Pinoox\Component\Template\View as ObjectPortal1;
use Pinoox\Portal\App\App;

/**
 * @method static View setView(array|string $folders, string $pathTheme)
 * @method static changeTheme(array|string $folders)
 * @method static string renderFile(string $name, array $parameters = [])
 * @method static bool existsFile(string $name)
 * @method static bool exists(string $name)
 * @method static array getAll()
 * @method static mixed get(int|string $index)
 * @method static View set(string $name, mixed $value)
 * @method static array engines()
 * @method static string render(string $name, array $parameters = [])
 * @method static View ready(string $name = '', array $parameters = [])
 * @method static string getContentReady()
 * @method static ObjectPortal2 path()
 * @method static \Pinoox\Component\Template\View ___()
 *
 * @see \Pinoox\Component\Template\View
 */
class View extends Portal
{
	public static function __register(): void
	{
		// theme names
		$folders = App::get('theme');

		// base path
		$pathTheme = Path::get(App::get('path-theme'));

		self::__bind(ObjectPortal1::class)->setArguments([
		    $folders,
		    $pathTheme,
		]);
	}


	public static function response(string $name, array $parameters = [], ?string $contentType = null,?string $charset = null): Response
	{
		$content = self::render($name, $parameters);
		$response = new Response($content);
		if (!empty($contentType))
		    $response->addContentType($contentType);
        if (!empty($charset))
		    $response->setCharset($charset);
		return $response;
	}

    public static function jsonResponse(string $name, array $parameters = [],?string $charset = null): JsonResponse
	{
		$content = self::render($name, $parameters);
		$response = new JsonResponse();
        $response->setJson($content);
        if (!empty($charset))
		    $response->setCharset($charset);
		return $response;
	}

    public static function jsResponse(string $name, array $parameters = [],?string $charset = 'UTF-8'): Response
	{
		return self::response($name, $parameters, 'application/javascript', $charset);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'view';
	}


	public static function __app(): string
	{
		return App::package();
	}


	/**
	 * Get exclude method names.
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
		    'set',
		    'ready'
		];
	}
}
