<?php

namespace Pinoox\Support;

use Pinoox\Component\Kernel\Loader;

class SystemConfig
{
    private static array $cache = [];

    public static function get(string $config, ?string $key = null, mixed $default = null): mixed
    {
        $data = self::load($config);

        if ($key === null || $key === '') {
            return $data;
        }

        foreach (explode('.', $key) as $part) {
            if (!is_array($data) || !array_key_exists($part, $data)) {
                return $default;
            }

            $data = $data[$part];
        }

        return $data;
    }

    public static function path(string $key, ?string $default = null): string
    {
        $value = self::get('paths', $key, $default ?? $key);

        return self::resolvePath((string)$value);
    }

    public static function rawPath(string $key, ?string $default = null): string
    {
        return (string)self::get('paths', $key, $default ?? $key);
    }

    public static function rootPath(): string
    {
        $basePath = Loader::getBasePath();

        if (is_string($basePath) && $basePath !== '') {
            return rtrim(str_replace('\\', '/', $basePath), '/');
        }

        return defined('PINOOX_BASE_PATH')
            ? rtrim(str_replace('\\', '/', \PINOOX_BASE_PATH), '/')
            : dirname(__DIR__, 2);
    }

    public static function corePath(string $path = ''): string
    {
        $corePath = defined('PINOOX_CORE_PATH')
            ? rtrim(str_replace('\\', '/', \PINOOX_CORE_PATH), '/')
            : self::resolveBasePath(self::env('PINOOX_CORE_PATH', self::env('PINOOX_PINCORE_PATH', 'pincore')));

        return self::join($corePath, $path);
    }

    public static function systemPath(string $path = ''): string
    {
        return self::join(self::resolveBasePath(self::env('PINOOX_SYSTEM_PATH', 'system')), $path);
    }

    public static function resolvePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));

        if ($path === '') {
            return self::rootPath();
        }

        if ($path === '~') {
            return self::rootPath();
        }

        if (str_starts_with($path, '~/')) {
            return self::join(self::rootPath(), substr($path, 2));
        }

        foreach ([
            '~system' => self::systemPath(),
            '~pincore' => self::corePath(),
            '~pinker' => self::pathWithoutAlias('pinker', 'pinker'),
            '~storage' => self::pathWithoutAlias('storage', 'storage'),
        ] as $alias => $basePath) {
            if ($path === $alias) {
                return $basePath;
            }

            if (str_starts_with($path, $alias . '/')) {
                return self::join($basePath, substr($path, strlen($alias) + 1));
            }
        }

        if (preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/')) {
            return rtrim($path, '/');
        }

        return self::join(self::rootPath(), $path);
    }

    public static function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string)$value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    private static function load(string $config): array
    {
        if (array_key_exists($config, self::$cache)) {
            return self::$cache[$config];
        }

        $file = self::systemPath('config/' . $config . '.config.php');
        $loaded = is_file($file) ? require $file : [];

        return self::$cache[$config] = is_array($loaded) ? $loaded : [];
    }

    private static function pathWithoutAlias(string $key, string $default): string
    {
        $value = self::get('paths', $key, $default);
        $value = trim(str_replace('\\', '/', (string)$value));

        if ($value === '~') {
            return self::rootPath();
        }

        if (str_starts_with($value, '~/')) {
            return self::join(self::rootPath(), substr($value, 2));
        }

        if (str_starts_with($value, '~system')) {
            return self::join(self::systemPath(), substr($value, strlen('~system')));
        }

        if (str_starts_with($value, '~pincore')) {
            return self::join(self::corePath(), substr($value, strlen('~pincore')));
        }

        return self::resolveBasePath($value);
    }

    private static function resolveBasePath(mixed $path): string
    {
        $path = trim(str_replace('\\', '/', (string)$path));

        if ($path === '' || $path === '~') {
            return self::rootPath();
        }

        if (str_starts_with($path, '~/')) {
            return self::join(self::rootPath(), substr($path, 2));
        }

        if (preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/')) {
            return rtrim($path, '/');
        }

        return self::join(self::rootPath(), $path);
    }

    private static function join(string $basePath, string $path = ''): string
    {
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        $path = trim(str_replace('\\', '/', $path), '/');

        return $path === '' ? $basePath : $basePath . '/' . $path;
    }
}

