<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace App\com_pinoox_installer\Component;

class LangHelper
{
    public static function direction(?string $locale = null): string
    {
        $language = t('language', [], $locale);
        $direction = is_array($language) ? ($language['direction'] ?? 'ltr') : 'ltr';

        return in_array($direction, ['rtl', 'ltr'], true) ? $direction : 'ltr';
    }

    public static function languageNames(?string $locale = null): array
    {
        $language = t('language', [], $locale);

        if (!is_array($language)) {
            return [];
        }

        unset($language['direction']);

        return $language;
    }

    public static function forFrontend(?string $locale = null): array
    {
        return [
            'install' => t('install', [], $locale),
            'user' => t('user', [], $locale),
            'language' => self::languageNames($locale),
        ];
    }
}
