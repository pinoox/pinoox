<?php

namespace Pinoox\Component\Cache;

use Pinoox\Component\Cache\Store\ApiCacheStore;
use Pinoox\Component\Cache\Store\BootCacheStore;
use Pinoox\Component\Cache\Store\GraphQLCacheStore;
use Pinoox\Component\Cache\Store\PinkerCacheStore;
use Pinoox\Component\Cache\Store\RouteCacheStore;
use Pinoox\Component\Cache\Store\TwigCacheStore;
use Pinoox\Portal\App\AppEngine;

class AppCacheManager
{
    /** @var array<string, CacheStoreInterface>|null */

    private static ?array $stores = null;

    /**
     * @return array<string, CacheStoreInterface>
     */
    public static function stores(): array
    {
        if (self::$stores !== null) {
            return self::$stores;
        }

        $stores = [
            new RouteCacheStore(),
            new ApiCacheStore(),
            new BootCacheStore(),
            new TwigCacheStore(),
            new GraphQLCacheStore(),
            new PinkerCacheStore(),
        ];

        self::$stores = [];
        foreach ($stores as $store) {
            self::$stores[$store->name()] = $store;
        }

        return self::$stores;
    }

    /**
     * @param list<string>|null $only
     * @return array<string, bool>
     */
    public static function build(?string $package = null, ?array $only = null, bool $force = false): array
    {
        $results = [];

        foreach (self::packages($package) as $pkg) {
            foreach (self::selectedStores($only) as $name => $store) {
                if (!$force && !AppCacheConfig::storeEnabledForBuild($name, $pkg)) {
                    $results[$pkg . ':' . $name] = false;
                    continue;
                }

                if (!$force && $store->isFresh($pkg)) {
                    $results[$pkg . ':' . $name] = true;
                    continue;
                }

                $results[$pkg . ':' . $name] = $store->build($pkg);
            }
        }

        return $results;
    }

    /**
     * @param list<string>|null $only
     */
    public static function clear(?string $package = null, ?array $only = null): void
    {
        foreach (self::packages($package) as $pkg) {
            foreach (self::selectedStores($only) as $store) {
                $store->clear($pkg);
            }

            if ($only === null) {
                self::removeDirectory(AppCachePath::root($pkg));
            }
        }
    }

    /**
     * @return list<string>
     */
    public static function packages(?string $package): array
    {
        if ($package !== null && $package !== '' && $package !== 'all') {
            return [$package];
        }

        return array_keys(AppEngine::all());
    }

    /**
     * @param list<string>|null $only
     * @return array<string, CacheStoreInterface>
     */
    private static function selectedStores(?array $only): array
    {
        $stores = self::stores();

        if ($only === null || $only === []) {
            return $stores;
        }

        $selected = [];
        foreach ($only as $name) {
            $name = trim(strtolower((string) $name));
            if (isset($stores[$name])) {
                $selected[$name] = $stores[$name];
            }
        }

        return $selected;
    }

    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}

