<?php

namespace Pinoox\Component\Cache;

class AppCacheFingerprint
{
    /**
     * @param list<string> $files
     */
    public static function files(array $files): string
    {
        $files = array_values(array_unique(array_filter($files, static fn ($file) => is_string($file) && is_file($file))));
        sort($files);

        $parts = [];
        foreach ($files as $file) {
            $parts[] = str_replace('\\', '/', $file) . ':' . (filemtime($file) ?: 0) . ':' . (filesize($file) ?: 0);
        }

        return sha1(implode('|', $parts));
    }

    /**
     * @param list<string> $files
     */
    public static function isFresh(string $package, string $store, array $files): bool
    {
        $meta = AppCacheManifest::storeMeta($package, $store);
        if ($meta === null) {
            return false;
        }

        $checksum = self::files($files);

        return ($meta['checksum'] ?? '') === $checksum
            && PhpCacheFile::exists(AppCachePath::store($package, $store));
    }
}

