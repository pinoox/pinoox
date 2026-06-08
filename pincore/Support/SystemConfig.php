<?php

namespace Pinoox\Support;

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Store\Baker\Pinker;

class SystemConfig
{
    private static array $cache = [];

    /** Config names that must never go through Pinker (bootstrap / path resolution). */
    private const DIRECT_LOAD_CONFIGS = ['paths'];

    /** @var array<string, string> legacy path key → v3 key */
    private const PATH_KEY_ALIASES = [
        'system_config' => 'project_config',
        'system_registry' => 'project_registry',
        'system_router' => 'project_router',
        'system_lang' => 'platform_lang',
        'system_migrations' => 'platform_migrations',
        'system_seed' => 'platform_seed',
        'system_patches' => 'platform_patches',
        'system_models' => 'platform_models',
    ];

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
        $key = self::PATH_KEY_ALIASES[$key] ?? $key;
        $value = self::get('paths', $key, $default ?? $key);

        return self::resolvePath((string)$value);
    }

    /**
     * Resolve a platform resource directory (migrations, patches, seed).
     *
     * Uses the v3 pincore path first, then legacy system/ layout from older installs.
     */
    public static function platformPath(string $resource): string
    {
        $canonical = match ($resource) {
            'migrations' => self::path('platform_migrations'),
            'patches' => self::path('platform_patches'),
            'seed' => self::path('platform_seed'),
            default => throw new \InvalidArgumentException('Unknown platform resource: ' . $resource),
        };

        foreach (self::platformPathCandidates($resource) as $candidate) {
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        return $canonical;
    }

    /**
     * @return list<string>
     */
    private static function platformPathCandidates(string $resource): array
    {
        $root = self::rootPath();
        $core = self::corePath();

        return match ($resource) {
            'migrations' => [
                self::path('platform_migrations'),
                $core . '/database/migrations',
                $root . '/pincore/database/migrations',
                $root . '/system/database/migrations',
            ],
            'patches' => [
                self::path('platform_patches'),
                $core . '/patches',
                $root . '/pincore/patches',
                $root . '/system/patches',
            ],
            'seed' => [
                self::path('platform_seed'),
                $core . '/database/seed',
                $root . '/pincore/database/seed',
                $root . '/system/database/seed',
            ],
            default => [],
        };
    }

    public static function rawPath(string $key, ?string $default = null): string
    {
        $key = self::PATH_KEY_ALIASES[$key] ?? $key;

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
            : self::resolveBasePath(self::env('PINOOX_CORE_PATH', 'pincore'));

        return self::join($corePath, $path);
    }

    public static function configPath(string $path = ''): string
    {
        $override = self::env('PINOOX_CONFIG_PATH');

        if (is_string($override) && $override !== '') {
            return self::join(self::resolveBasePath($override), $path);
        }

        return self::corePath(self::join('config', $path));
    }

    public static function pinkerConfigPath(string $path = ''): string
    {
        return self::join(self::path('pinker'), self::join('config', $path));
    }

    /** @deprecated v3 — use {@see configPath()} */
    public static function systemPath(string $path = ''): string
    {
        return self::configPath($path);
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
            '~config' => self::configPath(),
            '~system' => self::configPath(),
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
            'true', '(true)', '1', '(1)' => true,
            'false', '(false)', '0', '(0)' => false,
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

        $mainFile = self::configPath($config . '.config.php');

        if (!is_file($mainFile)) {
            return self::$cache[$config] = [];
        }

        if (self::shouldLoadViaPinker($config, $mainFile)) {
            $bakedFile = self::pinkerConfigPath($config . '.config.php');
            $loaded = Pinker::create($mainFile, $bakedFile)->pickup();

            if (is_array($loaded)) {
                return self::$cache[$config] = $loaded;
            }
        }

        $loaded = require $mainFile;

        return self::$cache[$config] = is_array($loaded) ? $loaded : [];
    }

    private static function shouldLoadViaPinker(string $config, string $mainFile): bool
    {
        if (in_array($config, self::DIRECT_LOAD_CONFIGS, true)) {
            return false;
        }

        $bakedFile = self::pinkerConfigPath($config . '.config.php');

        if ($bakedFile === $mainFile) {
            return false;
        }

        $stateFile = self::join(
            self::pathWithoutAlias('pinker', 'pinker'),
            'state/config/' . $config . '.config.php',
        );

        return is_file($stateFile)
            || is_file($bakedFile)
            || \Pinoox\Component\Store\Baker\EnvSensitiveConfig::sourceUsesEnv($mainFile);
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

        if (str_starts_with($value, '~config')) {
            return self::join(self::configPath(), substr($value, strlen('~config')));
        }

        if (str_starts_with($value, '~system')) {
            return self::join(self::configPath(), substr($value, strlen('~system')));
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
