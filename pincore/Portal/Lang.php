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

use Pinoox\Component\Lang\Source\FileLangSource;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\App;

/**
 * @method static \Pinoox\Component\Lang\Lang create(\Pinoox\Component\Lang\Source\LangSource $source)
 * @method static Lang load()
 * @method static Lang locale($locale)
 * @method static string get(string $key, array $replacements = [])
 * @method static string getChoice(string $key, $count = 0)
 * @method static Lang setFallback($lang)
 * @method static pluralize($key, $count)
 * @method static \Pinoox\Component\Lang\Lang ___()
 *
 * @see \Pinoox\Component\Lang\Lang
 */
class Lang extends Portal
{
    const locale = 'en';
    const folder = 'lang';
    const ext = '.lang.php';

    public static function __register(): void
    {
        $path = Path::get(self::folder);
        self::__bind(FileLangSource::class, 'source')
            ->setArgument('path', $path)
            ->setArgument('locale', App::get('lang'))
            ->setArgument('ext', self::ext);

        self::__bind(\Pinoox\Component\Lang\Lang::class)->setArguments([
            self::__ref('source')
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
            'locale',
            'setFallback'
        ];
    }
}
