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
use Pinoox\Component\Template\Seo\SeoMeta;
use Pinoox\Component\Template\Reference\TemplatePathReference as ObjectPortal2;
use Pinoox\Component\Template\View as ObjectPortal1;
use Pinoox\Component\Cache\AppCacheConfig;
use Pinoox\Component\Template\Theme\ThemeStack;
use Pinoox\Portal\App\App;

/**
 * @method static \Pinoox\Component\Template\View setView(array|string $folders, string $pathTheme)
 * @method static \Pinoox\Component\Template\View changeTheme(array|string $folders)
 * @method static string renderFile(string $name, array $parameters = [])
 * @method static bool existsFile(string $name)
 * @method static bool exists(string $name)
 * @method static array getAll()
 * @method static mixed get(int|string $index)
 * @method static \Pinoox\Component\Template\View set(string $name, mixed $value)
 * @method static \Pinoox\Component\Template\View add(array $data)
 * @method static \Pinoox\Component\Template\View setData(array $data)
 * @method static array engines()
 * @method static string assets(string $link = '', bool $asPath = false, ?string $theme = null)
 * @method static string asstes(string $file = '')
 * @method static string render(array|null|string $name = NULL, array $parameters = [], bool $exist = true)
 * @method static string renderByEngine(array|null|string $name, array $parameters, bool $exist = true)
 * @method static \Pinoox\Component\Template\View ready(array|string $name = '', array $parameters = [], bool $exist = true)
 * @method static string getContentReady()
 * @method static array themePaths()
 * @method static array themeStack()
 * @method static ObjectPortal2 path()
 * @method static ObjectPortal1 ___()
 *
 * @see \Pinoox\Component\Template\View
 */
class View extends Portal
{
    public static function isFilesystemPath(string $path): bool
    {
        return ObjectPortal1::isFilesystemPath($path);
    }

    public static function __register(): void
    {
        $stack = ThemeStack::resolve(App::package());

        self::__bind(ObjectPortal1::class)->setArguments([
            $stack['paths'],
            '',
            self::getTwigOptions(),
        ]);
    }

    public static function response(
        string  $name,
        array   $parameters = [],
        ?string $contentType = null,
        ?string $charset = null,
    ): Response
    {
        $content = self::render($name, $parameters);
        $response = new Response($content);
        if (!empty($contentType))
            $response->addContentType($contentType);
        if (!empty($charset))
            $response->setCharset($charset);
        return $response;
    }

    private static function getFunctionsLoader(): array
    {
        $loader = App::get('loader');
        $loader = !empty($loader) && is_array($loader) ? $loader : [];
        return array_filter($loader, function ($key) {
            return strpos($key, '@') === 0;
        });
    }

    public static function getTwigOptions(): array
    {
        $twig = App::get('twig');
        $options = !empty($twig) && is_array($twig) ? $twig : [];

        return array_merge($options, AppCacheConfig::twigOptions(App::package()));
    }

    public static function jsonResponse(string $name, array $parameters = [], ?string $charset = null): JsonResponse
    {
        $content = self::render($name, $parameters);
        $response = new JsonResponse();
        $response->setJson($content);
        if (!empty($charset))
            $response->setCharset($charset);
        return $response;
    }

    public static function jsResponse(string $name, array $parameters = [], ?string $charset = 'UTF-8'): Response
    {
        return self::response($name, $parameters, 'application/javascript', $charset);
    }

    /**
     * @param array<string, mixed>|SeoMeta $seo
     */
    public static function shareSeo(array|SeoMeta $seo): void
    {
        $meta = $seo instanceof SeoMeta ? $seo : SeoMeta::fromArray($seo);
        self::set('_seo', $meta);
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
        return [];
    }
}

