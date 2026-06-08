<?php

namespace Pinoox\Support;

use Pinoox\Component\Kernel\Loader;

class SystemApp
{
    /** Logical package name for project-level config (legacy: system). */
    public const PACKAGE = 'config';

    /** Path alias for project config directory. Legacy ~system maps here too. */
    public const PATH_ALIAS = 'config';

    /** @deprecated v3 */
    public const LEGACY_PACKAGE = 'system';

    /** @deprecated v3 */
    public const LEGACY_PATH_ALIAS = 'system';

    public static function basePath(): string
    {
        return SystemConfig::configPath();
    }

    public static function path(string $path = ''): string
    {
        return self::basePath() . self::suffix($path);
    }

    public static function legacyCorePath(string $path = ''): string
    {
        return SystemConfig::corePath($path);
    }

    public static function existingPath(string $path, bool $fallbackToCore = true): string
    {
        $configPath = self::path($path);

        if (is_file($configPath) || is_dir($configPath) || !$fallbackToCore) {
            return $configPath;
        }

        return self::legacyCorePath($path);
    }

    public static function stripPathAlias(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($path, '~')) {
            $path = ltrim(substr($path, 1), '/');
        }

        foreach ([self::PATH_ALIAS, self::LEGACY_PATH_ALIAS] as $alias) {
            if ($path === $alias) {
                return '';
            }

            if (str_starts_with($path, $alias . '/')) {
                return substr($path, strlen($alias) + 1);
            }
        }

        return null;
    }

    private static function suffix(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        return $path === '' ? '' : '/' . $path;
    }
}
