<?php

namespace Pinoox\Component\Package\AppEnv;

use Pinoox\Component\Store\Config\ConfigInterface;

/**
 * Applies apps/{package}/.env and theme/{name}/.env onto app config (app.php).
 */
final class AppEnvBridge
{
    /** @var array<string, array<string, mixed>> */
    private static array $effective = [];

    /** @var array<string, array{app: ?string, theme: ?string}> */
    private static array $sources = [];

    public static function apply(ConfigInterface $config, string $package, string $appRoot): void
    {
        AppEnvLoader::forget($package);

        $appVars = AppEnvLoader::loadApp($package, $appRoot);
        self::applyVars($config, $appVars);

        $pathTheme = (string) ($config->get('path-theme') ?? 'theme');
        $themeName = self::resolveThemeName($config);
        $themeDir = rtrim(str_replace('\\', '/', $appRoot), '/')
            . '/'
            . trim($pathTheme, '/')
            . '/'
            . $themeName;

        $themeVars = AppEnvLoader::loadTheme($package, $themeName, $themeDir);
        self::applyVars($config, $themeVars);

        self::$effective[$package] = self::buildEffective($appVars, $themeVars);
        self::$sources[$package] = [
            'app' => AppEnvLoader::appEnvFiles($appRoot),
            'theme' => AppEnvLoader::themeEnvFiles($themeDir),
        ];
    }

    /**
     * Resolved env values for a package (theme wins over app).
     *
     * @return array<string, mixed>
     */
    public static function effective(string $package): array
    {
        return self::$effective[$package] ?? [];
    }

    /**
     * @return array{app: ?string, theme: ?string}
     */
    public static function sources(string $package): array
    {
        return self::$sources[$package] ?? ['app' => null, 'theme' => null];
    }

    public static function get(string $package, string $key, mixed $default = null): mixed
    {
        $key = strtoupper($key);

        return self::$effective[$package][$key] ?? $default;
    }

    public static function reset(): void
    {
        self::$effective = [];
        self::$sources = [];
        AppEnvLoader::reset();
    }

    /**
     * @param array<string, string> $vars
     */
    private static function applyVars(ConfigInterface $config, array $vars): void
    {
        foreach (AppEnvCatalog::keyNames() as $key) {
            if (!array_key_exists($key, $vars)) {
                continue;
            }

            $cast = AppEnvCatalog::cast($key, $vars[$key]);

            if ($cast === null && $vars[$key] === '') {
                continue;
            }

            self::setConfigPath($config, AppEnvCatalog::KEYS[$key]['path'], $cast);
        }
    }

    private static function setConfigPath(ConfigInterface $config, string $path, mixed $value): void
    {
        $parts = explode('.', $path);
        $leaf = array_pop($parts);
        $pointer = $parts === [] ? null : implode('.', $parts);

        if ($pointer === null) {
            $config->set($leaf, $value);

            return;
        }

        $config->setLinear($pointer, $leaf, $value);
    }

    private static function resolveThemeName(ConfigInterface $config): string
    {
        $theme = $config->get('theme', 'default');

        if (is_string($theme) && $theme !== '') {
            return $theme;
        }

        if (is_array($theme)) {
            foreach (['active', 'name', 'default'] as $candidate) {
                if (!empty($theme[$candidate]) && is_string($theme[$candidate])) {
                    return $theme[$candidate];
                }
            }
        }

        return 'default';
    }

    /**
     * @param array<string, string> $appVars
     * @param array<string, string> $themeVars
     * @return array<string, mixed>
     */
    private static function buildEffective(array $appVars, array $themeVars): array
    {
        $merged = $appVars;

        foreach ($themeVars as $key => $value) {
            $merged[$key] = $value;
        }

        $effective = [];

        foreach (AppEnvCatalog::keyNames() as $key) {
            if (!array_key_exists($key, $merged)) {
                continue;
            }

            $effective[$key] = AppEnvCatalog::cast($key, $merged[$key]);
        }

        return $effective;
    }
}
