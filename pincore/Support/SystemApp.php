<?php

namespace Pinoox\Support;

use Pinoox\Component\Kernel\Loader;

class SystemApp
{

    public const PACKAGE = 'system';

    public const PATH_ALIAS = 'system';

    public static function basePath(): string
    {
        return SystemConfig::systemPath();
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
        $systemPath = self::path($path);

        if (is_file($systemPath) || is_dir($systemPath) || !$fallbackToCore) {
            return $systemPath;
        }

        return self::legacyCorePath($path);
    }

    public static function stripPathAlias(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($path, '~')) {
            $path = ltrim(substr($path, 1), '/');
        }

        if ($path === self::PATH_ALIAS) {
            return '';
        }

        if (str_starts_with($path, self::PATH_ALIAS . '/')) {
            return substr($path, strlen(self::PATH_ALIAS) + 1);
        }

        return null;
    }

    private static function rootPath(): string
    {
        return SystemConfig::rootPath();
    }

    private static function suffix(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        return $path === '' ? '' : '/' . $path;
    }
}

