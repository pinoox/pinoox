<?php

namespace Pinoox\Portal;

use Pinoox\Component\Cache\AppCacheConfig;
use Pinoox\Component\Cache\AppCacheManager;
use Pinoox\Component\Cache\AppCachePath;
use Pinoox\Component\Source\Portal;

/**
 * @method static array<string, bool> build(?string $package = null, ?array $only = null, bool $force = false)
 * @method static void clear(?string $package = null, ?array $only = null)
 * @method static array<string, \Pinoox\Component\Cache\CacheStoreInterface> stores()
 * @method static bool enabled(?string $package = null)
 * @method static string root(string $package)
 * @method static AppCacheManager ___()
 *
 * @see AppCacheManager
 */
class AppCache extends Portal
{
    public static function __register(): void
    {
        self::__bind(AppCacheManager::class);
    }

    public static function __name(): string
    {
        return 'app.cache';
    }

    public static function __exclude(): array
    {
        return [];
    }

    public static function __callback(): array
    {
        return [
            'build',
            'clear',
            'stores',
            'enabled',
        ];
    }

    public static function root(string $package): string
    {
        return AppCachePath::root($package);
    }

    public static function enabled(?string $package = null): bool
    {
        return AppCacheConfig::enabled($package);
    }
}

