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

use Illuminate\Contracts\Translation\Loader as ObjectPortal3;
use Illuminate\Translation\MessageSelector as ObjectPortal2;
use Illuminate\Translation\Translator as ObjectPortal1;
use Pinoox\Component\Source\Portal;
use Pinoox\Component\Translator\Loader\FileLoader;
use Pinoox\Component\Translator\Translator;
use Pinoox\Portal\App\App;

/**
 * @method static Lang addPath(string $path)
 * @method static string replaceNested($key, array $replace = [], $locale = NULL, $fallback = true)
 * @method static Lang addJsonPath($path)
 * @method static Lang addPathAndJson(string $path)
 * @method static bool existsLocale($locale)
 * @method static bool hasForLocale($key, $locale = NULL)
 * @method static bool has($key, $locale = NULL, $fallback = true)
 * @method static string|array get($key, array $replace = [], $locale = NULL, $fallback = true)
 * @method static string choice($key, $number, array $replace = [], $locale = NULL)
 * @method static addLines(array $lines, $locale, $namespace = '*')
 * @method static Lang load($namespace, $group, $locale)
 * @method static ObjectPortal1 handleMissingKeysUsing(?callable $callback)
 * @method static addNamespace($namespace, $hint)
 * @method static array parseKey($key)
 * @method static determineLocalesUsing($callback)
 * @method static ObjectPortal2 getSelector()
 * @method static setSelector(\Illuminate\Translation\MessageSelector $selector)
 * @method static ObjectPortal3 getLoader()
 * @method static string locale()
 * @method static string getLocale()
 * @method static setLocale($locale)
 * @method static string getFallback()
 * @method static Lang setFallback($fallback)
 * @method static setLoaded(array $loaded)
 * @method static stringable($class, $handler = NULL)
 * @method static setParsedKey($key, $parsed)
 * @method static flushParsedKeys()
 * @method static macro($name, $macro)
 * @method static mixin($mixin, $replace = true)
 * @method static bool hasMacro($name)
 * @method static flushMacros()
 * @method static \Pinoox\Component\Translator\Translator ___()
 *
 * @see \Pinoox\Component\Translator\Translator
 */
class Lang extends Portal
{
    private const localeFallback = 'en';
    private const folder = 'lang';
    private const ext = '.lang';

    public static function __register(): void
    {
        $localeFallback = App::get('lang_fallback') ?? self::localeFallback;
        $paths = [
            Path::get(self::folder),
            View::asstes('lang'),
        ];

        self::__bind(FileLoader::class, 'loader')
            ->setArgument('path', $paths)
            ->setArgument('postfix', self::ext);

        self::__bind(Translator::class)->setArguments([
            self::__ref('loader'),
            App::get('lang')
        ])->addMethodCall('setFallback', [
            $localeFallback
        ]);
    }


    public static function __app(): string
    {
        return App::package();
    }


    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'lang';
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
            'load',
            'setFallback'
        ];
    }
}
