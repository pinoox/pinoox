<?php

namespace Pinoox\Component\Cache;

use Pinoox\Support\SystemConfig;

final class AppCachePath
{
    public static function root(string $package): string
    {
        return rtrim(str_replace('\\', '/', SystemConfig::path('pinker')), '/')
            . '/apps/' . $package . '/cache';
    }

    public static function store(string $package, string $store): string
    {
        return self::root($package) . '/' . $store . '.' . PhpCacheFile::EXT;
    }

    public static function legacyStore(string $package, string $store): string
    {
        return self::root($package) . '/' . $store . '.' . PhpCacheFile::LEGACY_EXT;
    }

    public static function manifest(string $package): string
    {
        return self::root($package) . '/manifest.' . PhpCacheFile::EXT;
    }

    public static function twig(string $package): string
    {
        return self::root($package) . '/twig';
    }

    public static function ensureDir(string $package): void
    {
        $dir = self::root($package);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

