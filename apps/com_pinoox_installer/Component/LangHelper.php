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
    private static function loadRequirements(): void
    {
        if (function_exists('pinoox_php_requirement_tokens')) {
            return;
        }

        require_once dirname(__DIR__, 3) . '/platform/launcher/requirements.php';
    }

    private static function langGroup(string $key, ?string $locale = null): array
    {
        $value = t($key, [], $locale);

        return is_array($value) ? $value : [];
    }

    private static function applyRequirementTokens(array $strings): array
    {
        self::loadRequirements();

        foreach ($strings as $name => $text) {
            if (!is_string($text) || !str_contains($text, '{')) {
                continue;
            }

            $strings[$name] = pinoox_replace_requirement_tokens($text);
        }

        return $strings;
    }

    public static function direction(?string $locale = null): string
    {
        if ($locale === 'fa') {
            return 'rtl';
        }

        if ($locale === 'en') {
            return 'ltr';
        }

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
            'install' => self::applyRequirementTokens(self::langGroup('install', $locale)),
            'user' => self::langGroup('user', $locale),
            'language' => self::languageNames($locale),
            'requirements' => self::platformRequirements(),
        ];
    }

    /**
     * @return array{php: array{minimum: string, constraint: string, php_short: string}}
     */
    public static function platformRequirements(): array
    {
        self::loadRequirements();
        $tokens = pinoox_php_requirement_tokens();

        return [
            'php' => [
                'minimum' => $tokens['required'],
                'constraint' => $tokens['constraint'],
                'php_short' => $tokens['php_short'],
            ],
        ];
    }
}

