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

require_once __DIR__ . '/core-path.php';

function pinoox_base_path(): string
{
    if (defined('PINOOX_BASE_PATH')) {
        return rtrim(str_replace('\\', '/', PINOOX_BASE_PATH), '/');
    }

    return dirname(__DIR__, 2);
}

function pinoox_core_path(): string
{
    if (defined('PINOOX_CORE_PATH')) {
        return rtrim(str_replace('\\', '/', PINOOX_CORE_PATH), '/');
    }

    return pinoox_base_path() . '/vendor/pinoox/pincore';
}

function pinoox_system_path(): string
{
    $configured = getenv('PINOOX_CONFIG_PATH') ?: getenv('PINOOX_SYSTEM_PATH');

    if (is_string($configured) && $configured !== '') {
        $configured = pinoox_normalize_path($configured);

        if (!preg_match('/^[A-Za-z]:\//', $configured) && !str_starts_with($configured, '/')) {
            $configured = pinoox_normalize_path(pinoox_base_path() . '/' . $configured);
        }

        return $configured;
    }

    return pinoox_core_path() . '/config';
}

function pinoox_public_system_path(): string
{
    $systemPath = pinoox_system_path();
    $basePath = pinoox_base_path();

    if (str_starts_with($systemPath, $basePath . '/')) {
        return ltrim(substr($systemPath, strlen($basePath)), '/');
    }

    return 'system';
}

function pinoox_system_app_path(): string
{
    return pinoox_system_path();
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

    $defaults = pinoox_requirement_defaults();

    $require = [];
    $php = null;
    $rootFile = pinoox_base_path() . '/composer.json';

    if (is_file($rootFile)) {
        $rootJson = json_decode((string) file_get_contents($rootFile), true);

        if (is_array($rootJson) && isset($rootJson['require']['php'])) {
            $php = pinoox_normalize_php_constraint((string) $rootJson['require']['php']);
        }
    }

    foreach (pinoox_composer_json_files() as $file) {
        if (!is_file($file)) {
            continue;
        }

        $json = json_decode((string) file_get_contents($file), true);

        if (!is_array($json)) {
            continue;
        }

        $require = array_merge($require, is_array($json['require'] ?? null) ? $json['require'] : []);
    }

    if ($require === [] && $php === null) {
        return $cache = $defaults;
    }

    if ($php === null) {
        $php = pinoox_normalize_php_constraint((string) ($require['php'] ?? '8.2.0'));
    }

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
        return '8.2.0';
    }

    if (preg_match('/(\d+\.\d+(?:\.\d+)?)/', $constraint, $matches) !== 1) {
        return '8.2.0';
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
        return '^8.2';
    }

    $json = json_decode((string) file_get_contents($file), true);

    if (!is_array($json)) {
        return '^8.2';
    }

    $require = is_array($json['require'] ?? null) ? $json['require'] : [];

    return (string) ($require['php'] ?? '^8.2');
}

function pinoox_php_short_version(?string $minimum = null): string
{
    $minimum = $minimum ?? pinoox_min_php_version();

    if (preg_match('/^(\d+\.\d+)/', $minimum, $matches) === 1) {
        return $matches[1];
    }

    return $minimum;
}

/**
 * @return array{required: string, constraint: string, php_short: string}
 */
function pinoox_php_requirement_tokens(): array
{
    static $cache = null;

    if (is_array($cache)) {
        return $cache;
    }

    $minimum = pinoox_min_php_version();

    return $cache = [
        'required' => $minimum,
        'constraint' => pinoox_composer_php_constraint(),
        'php_short' => pinoox_php_short_version($minimum),
    ];
}

function pinoox_replace_requirement_tokens(string $text, ?array $tokens = null): string
{
    $tokens = $tokens ?? pinoox_php_requirement_tokens();

    foreach ($tokens as $key => $value) {
        $text = str_replace('{' . $key . '}', (string) $value, $text);
    }

    return $text;
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

function pinoox_launcher_path(): string
{
    return rtrim(str_replace('\\', '/', __DIR__), '/');
}

/**
 * @return list<string>
 */
function pinoox_composer_json_files(): array
{
    $base = pinoox_base_path();
    $core = rtrim(pinoox_core_path(), '/');

    return array_values(array_unique([
        $base . '/composer.json',
        $core . '/composer.json',
        $base . '/vendor/pinoox/pincore/composer.json',
    ]));
}

function pinoox_requirement_defaults(): array
{
    static $defaults = null;

    if (is_array($defaults)) {
        return $defaults;
    }

    $file = pinoox_launcher_path() . '/requirements.defaults.php';
    $loaded = is_file($file) ? require $file : [];

    if (!is_array($loaded)) {
        $loaded = [];
    }

    return $defaults = [
        'php' => (string) ($loaded['php'] ?? '8.2.0'),
        'extensions' => is_array($loaded['extensions'] ?? null) ? $loaded['extensions'] : [],
    ];
}

function pinoox_requirement_web_base(): string
{
    $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));

    return rtrim($scriptDir, '/');
}

function pinoox_requirement_asset_url(string $relative): string
{
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    $base = pinoox_requirement_web_base();

    return ($base === '' ? '' : $base) . '/assets/' . $relative;
}

function pinoox_requirement_locales(): array
{
    static $cache = null;

    if (is_array($cache)) {
        return $cache;
    }

    $locales = [];
    $pattern = pinoox_launcher_path() . '/lang/*/requirements.lang.php';

    foreach (glob($pattern) ?: [] as $file) {
        $code = basename(dirname($file));

        if (!preg_match('/^[a-z]{2}$/', $code) || isset($locales[$code])) {
            continue;
        }

        $data = require $file;
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $locales[$code] = (string) ($meta['name'] ?? strtoupper($code));
    }

    if ($locales === []) {
        $locales = ['en' => 'English'];
    }

    ksort($locales);

    return $cache = $locales;
}

function pinoox_requirement_normalize_locale(string $locale): string
{
    $locale = strtolower(trim($locale));
    $available = array_keys(pinoox_requirement_locales());

    return in_array($locale, $available, true) ? $locale : 'en';
}

function pinoox_requirement_locale(): string
{
    static $resolved = null;

    if (is_string($resolved)) {
        return $resolved;
    }

    $available = array_keys(pinoox_requirement_locales());

    if (isset($_GET['lang']) && is_string($_GET['lang'])) {
        $requested = pinoox_requirement_normalize_locale($_GET['lang']);

        if (in_array($requested, $available, true) && !headers_sent()) {
            setcookie('pinoox_requirement_lang', $requested, [
                'expires' => time() + 86400 * 365,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }

        if (in_array($requested, $available, true)) {
            return $resolved = $requested;
        }
    }

    if (isset($_COOKIE['pinoox_requirement_lang']) && is_string($_COOKIE['pinoox_requirement_lang'])) {
        $cookieLocale = pinoox_requirement_normalize_locale($_COOKIE['pinoox_requirement_lang']);

        if (in_array($cookieLocale, $available, true)) {
            return $resolved = $cookieLocale;
        }
    }

    return $resolved = pinoox_preferred_locale();
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
    $locale = pinoox_requirement_normalize_locale($locale);

    return pinoox_launcher_path() . '/lang/' . $locale . '/requirements.lang.php';
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

function pinoox_load_composer_helper(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    require_once __DIR__ . '/composer-helper.php';
    $loaded = true;
}

function pinoox_check_runtime_requirements(): void
{
    if (!pinoox_php_version_ok()) {
        pinoox_load_requirement_renderer();
        pinoox_render_php_requirement_error();
        exit(1);
    }

    if (!pinoox_vendor_installed()) {
        pinoox_load_requirement_renderer();
        pinoox_render_vendor_missing_error();
        exit(1);
    }
}
