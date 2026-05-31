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

/**
 * Runtime requirement checks without Composer autoload.
 * Loaded before vendor/autoload.php so low PHP versions still get a readable error.
 */

function pinoox_base_path(): string
{
    return dirname(__DIR__, 2);
}

function pinoox_core_path(): string
{
    return dirname(__DIR__);
}

/**
 * @return array{php: string, extensions: array<string, string>}
 */
function pinoox_composer_requirements(): array
{
    static $cache = null;

    if (is_array($cache)) {
        return $cache;
    }

    $defaults = [
        'php' => '8.1.0',
        'extensions' => [],
    ];

    $file = pinoox_base_path() . '/composer.json';

    if (!is_file($file)) {
        return $cache = $defaults;
    }

    $json = json_decode((string) file_get_contents($file), true);

    if (!is_array($json)) {
        return $cache = $defaults;
    }

    $require = is_array($json['require'] ?? null) ? $json['require'] : [];
    $php = pinoox_normalize_php_constraint((string) ($require['php'] ?? '8.1.0'));

    $extensions = [];

    foreach ($require as $package => $constraint) {
        if (!is_string($package) || strpos($package, 'ext-') !== 0) {
            continue;
        }

        $extensions[substr($package, 4)] = is_string($constraint) ? $constraint : '*';
    }

    return $cache = [
        'php' => $php,
        'extensions' => $extensions,
    ];
}

function pinoox_normalize_php_constraint(string $constraint): string
{
    $constraint = trim($constraint);

    if ($constraint === '') {
        return '8.1.0';
    }

    if (preg_match('/(\d+\.\d+(?:\.\d+)?)/', $constraint, $matches) !== 1) {
        return '8.1.0';
    }

    $version = $matches[1];

    if (substr_count($version, '.') === 1) {
        $version .= '.0';
    }

    return $version;
}

function pinoox_min_php_version(): string
{
    return pinoox_composer_requirements()['php'];
}

function pinoox_composer_php_constraint(): string
{
    $file = pinoox_base_path() . '/composer.json';

    if (!is_file($file)) {
        return '^8.1';
    }

    $json = json_decode((string) file_get_contents($file), true);

    if (!is_array($json)) {
        return '^8.1';
    }

    $require = is_array($json['require'] ?? null) ? $json['require'] : [];

    return (string) ($require['php'] ?? '^8.1');
}

function pinoox_php_version_ok(?string $minimum = null): bool
{
    $minimum = $minimum ?? pinoox_min_php_version();

    return version_compare(PHP_VERSION, $minimum, '>=');
}

function pinoox_vendor_autoload_path(): string
{
    return pinoox_base_path() . '/vendor/autoload.php';
}

function pinoox_vendor_installed(): bool
{
    return is_file(pinoox_vendor_autoload_path());
}

function pinoox_preferred_locale(): string
{
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));

    if (strpos($accept, 'fa') === 0 || strpos($accept, 'fa-ir') !== false) {
        return 'fa';
    }

    return 'en';
}

function pinoox_requirement_lang_path(string $locale): string
{
    $locale = in_array($locale, ['fa', 'en'], true) ? $locale : 'en';

    return pinoox_core_path() . '/lang/' . $locale . '/requirements.lang.php';
}

function pinoox_load_requirement_lang(string $locale): array
{
    static $cache = [];

    if (isset($cache[$locale])) {
        return $cache[$locale];
    }

    $file = pinoox_requirement_lang_path($locale);

    if (!is_file($file)) {
        $file = pinoox_requirement_lang_path('en');
    }

    $loaded = is_file($file) ? require $file : [];

    return $cache[$locale] = is_array($loaded) ? $loaded : [];
}

function pinoox_requirement_replace(string $text, array $replace = []): string
{
    foreach ($replace as $key => $value) {
        $text = str_replace('{' . $key . '}', (string) $value, $text);
    }

    return $text;
}

function pinoox_requirement_lang(string $locale, string $section): array
{
    $lang = pinoox_load_requirement_lang($locale);
    $fallback = $locale !== 'en' ? pinoox_load_requirement_lang('en') : [];
    $sectionData = $lang[$section] ?? $fallback[$section] ?? [];

    return is_array($sectionData) ? $sectionData : [];
}

function pinoox_load_requirement_renderer(): void
{
    require_once __DIR__ . '/render-requirement-error.php';
}

function pinoox_check_runtime_requirements(): void
{
    if (!pinoox_php_version_ok()) {
        pinoox_load_requirement_renderer();
        pinoox_render_php_requirement_error();
        exit(1);
    }

    if (PHP_SAPI !== 'cli' && !pinoox_vendor_installed()) {
        pinoox_load_requirement_renderer();
        pinoox_render_vendor_missing_error();
        exit(1);
    }
}
