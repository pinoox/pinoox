<?php

namespace Pinoox\Component\Router\Action;

use Pinoox\Component\Cache\AppCachePath;
use Pinoox\Component\Cache\PhpCacheFile;
use Pinoox\Support\SystemConfig;

class ActionCache
{
    public static function path(string $package): string
    {
        return AppCachePath::store($package, 'routes');
    }

    /** @deprecated Old global layout before per-app pinker/apps/{package}/cache/ */

    public static function legacyGlobalPath(string $package): string
    {
        return rtrim(str_replace('\\', '/', SystemConfig::path('pinker')), '/')
            . '/cache/actions/' . $package . '.' . PhpCacheFile::EXT;
    }

    /** @return list<array<string, mixed>>|null */

    public static function load(string $package): ?array
    {
        $data = PhpCacheFile::read(self::path($package));
        if ($data === null) {
            $data = PhpCacheFile::read(self::legacyGlobalPath($package));
            if ($data === null) {
                return null;
            }

            $actions = self::extractActions($data);
            if ($actions !== null) {
                self::save($package, $actions);
            }
        }

        return self::extractActions($data);
    }

    /** @return list<array<string, mixed>>|null */

    private static function extractActions(array $data): ?array
    {
        if (isset($data['actions']) && is_array($data['actions'])) {
            return $data['actions'];
        }

        return $data;
    }

    /** @param list<array<string, mixed>> $manifest */

    public static function save(string $package, array $manifest): void
    {
        PhpCacheFile::write(self::path($package), [
            'actions' => $manifest,
        ]);
    }

    /** @param list<string> $routeFiles */

    public static function isStale(string $package, array $routeFiles): bool
    {
        $path = self::path($package);
        if (!PhpCacheFile::exists($path)) {
            return true;
        }

        $cacheFile = is_file($path) ? $path : PhpCacheFile::legacyPath($path);
        $cacheTime = filemtime($cacheFile) ?: 0;
        foreach ($routeFiles as $file) {
            if (is_file($file) && (filemtime($file) ?: 0) > $cacheTime) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */

    public static function resolveRouteFiles(string $package, string $appPath, array $configuredRoutes): array
    {
        $files = [];
        foreach ($configuredRoutes as $route) {
            $candidate = is_string($route) ? $appPath . '/' . ltrim($route, '/') : null;
            if ($candidate !== null && is_file($candidate)) {
                $files[] = str_replace('\\', '/', $candidate);
            }
        }

        return $files;
    }
}

