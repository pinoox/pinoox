<?php

namespace Pinoox\Component\Package\AppEnv;

use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Support\SystemConfig;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Reads scoped .env files for an app and its active theme (no global $_ENV pollution).
 *
 * Layer order (Symfony-style, later wins):
 *   .env → .env.local → .env.{APP_ENV} → .env.{APP_ENV}.local
 */
final class AppEnvLoader
{
    /** @var array<string, array<string, string>> */
    private static array $appValues = [];

    /** @var array<string, array<string, array<string, string>>> */
    private static array $themeValues = [];

    /**
     * @return array<string, string>
     */
    public static function loadApp(string $package, string $appRoot): array
    {
        if (isset(self::$appValues[$package])) {
            return self::$appValues[$package];
        }

        return self::$appValues[$package] = self::readLayeredDirectory($appRoot);
    }

    /**
     * @return array<string, string>
     */
    public static function loadTheme(string $package, string $themeName, string $themeDir): array
    {
        if (isset(self::$themeValues[$package][$themeName])) {
            return self::$themeValues[$package][$themeName];
        }

        self::$themeValues[$package] ??= [];

        return self::$themeValues[$package][$themeName] = self::readLayeredDirectory($themeDir);
    }

    /**
     * @return list<string> Existing env file paths for an app (for diagnostics).
     */
    public static function appEnvFiles(string $appRoot): array
    {
        return self::existingLayerFiles($appRoot);
    }

    /**
     * @return list<string>
     */
    public static function themeEnvFiles(string $themeDir): array
    {
        return self::existingLayerFiles($themeDir);
    }

    /**
     * @return array<string, string>
     */
    private static function readLayeredDirectory(string $directory): array
    {
        $merged = [];

        foreach (self::layerFilePaths($directory) as $path) {
            if (!is_file($path)) {
                continue;
            }

            foreach (self::readFile($path) as $key => $value) {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * @return list<string>
     */
    private static function layerFilePaths(string $directory): array
    {
        $directory = rtrim(str_replace('\\', '/', $directory), '/');
        $env = self::runtimeEnvName();
        $skipLocal = $env === RuntimeMode::TEST;

        $files = [
            $directory . '/.env',
        ];

        if (!$skipLocal) {
            $files[] = $directory . '/.env.local';
        }

        $files[] = $directory . '/.env.' . $env;

        if (!$skipLocal) {
            $files[] = $directory . '/.env.' . $env . '.local';
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private static function existingLayerFiles(string $directory): array
    {
        return array_values(array_filter(
            self::layerFilePaths($directory),
            static fn (string $path) => is_file($path),
        ));
    }

    private static function runtimeEnvName(): string
    {
        return RuntimeMode::fromEnv();
    }

    /**
     * @return array<string, string>
     */
    private static function readFile(string $path): array
    {
        $content = (string) @file_get_contents($path);

        if ($content === '') {
            return [];
        }

        try {
            $parsed = (new Dotenv())->parse($content);
        } catch (\Throwable) {
            return [];
        }

        $normalized = [];

        foreach ($parsed as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $normalized[strtoupper($key)] = is_scalar($value) || $value === null
                ? (string) $value
                : '';
        }

        return $normalized;
    }

    public static function reset(): void
    {
        self::$appValues = [];
        self::$themeValues = [];
    }

    public static function forget(string $package): void
    {
        unset(self::$appValues[$package], self::$themeValues[$package]);
    }
}
