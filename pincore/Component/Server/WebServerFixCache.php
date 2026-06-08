<?php

namespace Pinoox\Component\Server;

use Pinoox\Component\Cache\AppCachePath;
use Pinoox\Component\Cache\PhpCacheFile;
use Pinoox\Support\SystemConfig;

final class WebServerFixCache
{
    private const STORE = 'web_server_fix';

    public static function path(string $package): string
    {
        return AppCachePath::store($package, self::STORE);
    }

    /**
     * @return list<array{relative: string, name: string, full?: string}>
     */
    public static function load(string $package): array
    {
        $data = PhpCacheFile::read(self::path($package));

        if (!is_array($data)) {
            return [];
        }

        $paths = $data['paths'] ?? $data;

        if (!is_array($paths)) {
            return [];
        }

        $entries = [];

        foreach ($paths as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $relative = $entry['relative'] ?? null;

            if (!is_string($relative) || $relative === '') {
                continue;
            }

            $entries[] = [
                'relative' => WebServerFix::normalizePath($relative),
                'name' => is_string($entry['name'] ?? null) ? $entry['name'] : '',
                'full' => is_string($entry['full'] ?? null) ? WebServerFix::normalizePath($entry['full']) : null,
            ];
        }

        return $entries;
    }

    /**
     * @param list<array{relative: string, name: string, full?: string|null}> $entries
     */
    public static function save(string $package, array $entries): void
    {
        AppCachePath::ensureDir($package);

        PhpCacheFile::write(self::path($package), [
            'paths' => array_values($entries),
        ]);

        WebServerFix::resetResolvedPaths();
    }

    /**
     * @param list<array{relative: string, name: string, full?: string|null}> $entries
     */
    public static function merge(string $package, array $entries): void
    {
        $existing = [];

        foreach (self::load($package) as $entry) {
            $existing[$entry['relative']] = $entry;
        }

        foreach ($entries as $entry) {
            $relative = WebServerFix::normalizePath($entry['relative']);
            $existing[$relative] = [
                'relative' => $relative,
                'name' => $entry['name'] ?? ($existing[$relative]['name'] ?? ''),
                'full' => $entry['full'] ?? ($existing[$relative]['full'] ?? null),
            ];
        }

        self::save($package, array_values($existing));
    }

    /**
     * @return list<string>
     */
    public static function allRelativePaths(): array
    {
        $paths = [];

        foreach (self::packagesWithCache() as $package) {
            foreach (self::load($package) as $entry) {
                $paths[$entry['relative']] = $entry['relative'];
            }
        }

        return array_values($paths);
    }

    /**
     * @return list<string>
     */
    private static function packagesWithCache(): array
    {
        $packages = [];

        $appsRoot = rtrim(str_replace('\\', '/', SystemConfig::path('pinker')), '/') . '/apps';

        if (is_dir($appsRoot)) {
            foreach (scandir($appsRoot) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                if (PhpCacheFile::exists(self::path($entry))) {
                    $packages[$entry] = $entry;
                }
            }
        }

        foreach (WebServerFix::routerMap() as $package) {
            if (is_string($package) && $package !== '') {
                $packages[$package] = $package;
            }
        }

        return array_values($packages);
    }
}
