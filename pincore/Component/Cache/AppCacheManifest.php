<?php

namespace Pinoox\Component\Cache;

class AppCacheManifest
{

    public const SCHEMA = 1;

    /**
     * @return array<string, mixed>|null
     */
    public static function read(string $package): ?array
    {
        $data = PhpCacheFile::read(AppCachePath::manifest($package));

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<string, array<string, mixed>> $stores
     */
    public static function write(string $package, array $stores): void
    {
        AppCachePath::ensureDir($package);

        PhpCacheFile::write(AppCachePath::manifest($package), [
            'schema' => self::SCHEMA,
            'package' => $package,
            'built_at' => time(),
            'stores' => $stores,
        ]);
    }

    public static function touchStore(string $package, string $store, array $meta = []): void
    {
        $manifest = self::read($package) ?? [
            'schema' => self::SCHEMA,
            'package' => $package,
            'built_at' => time(),
            'stores' => [],
        ];

        $manifest['stores'][$store] = array_merge([
            'built_at' => time(),
        ], $meta);

        $manifest['built_at'] = time();

        AppCachePath::ensureDir($package);
        PhpCacheFile::write(AppCachePath::manifest($package), $manifest);
    }

    public static function storeMeta(string $package, string $store): ?array
    {
        $manifest = self::read($package);

        return $manifest['stores'][$store] ?? null;
    }
}

