<?php

namespace Pinoox\Component\Cache\Store;

use Pinoox\Component\Cache\AppCacheConfig;
use Pinoox\Component\Cache\AppCacheFingerprint;
use Pinoox\Component\Cache\AppCacheManifest;
use Pinoox\Component\Cache\AppCachePath;
use Pinoox\Component\Cache\CacheStoreInterface;
use Pinoox\Component\Cache\PhpCacheFile;
use Pinoox\Component\Router\Action\ActionCache;
use Pinoox\Component\Router\Action\ActionRegistry;
use Pinoox\Portal\App\AppEngine;

class RouteCacheStore implements CacheStoreInterface
{
    public function name(): string
    {
        return 'routes';
    }

    public function path(string $package): string
    {
        return AppCachePath::store($package, $this->name());
    }

    public function isFresh(string $package): bool
    {
        return AppCacheFingerprint::isFresh($package, $this->name(), $this->sourceFiles($package));
    }

    public function build(string $package): bool
    {
        AppEngine::router($package);
        AppCachePath::ensureDir($package);

        $manifest = ActionRegistry::exportManifest($package);
        ActionCache::save($package, $manifest);

        AppCacheManifest::touchStore($package, $this->name(), [
            'checksum' => AppCacheFingerprint::files($this->sourceFiles($package)),
            'count' => count($manifest),
        ]);

        return true;
    }

    public function clear(string $package): void
    {
        PhpCacheFile::unlink($this->path($package));
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public static function loadActions(string $package): ?array
    {
        if (!AppCacheConfig::storeEnabled('routes', $package)) {
            return null;
        }

        $store = new self();
        if (!$store->isFresh($package)) {
            return null;
        }

        return ActionCache::load($package);
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(string $package): array
    {
        $manager = AppEngine::manager($package);
        $routes = $manager->config()->get('router.routes') ?? [];
        $routes = is_array($routes) ? $routes : [];

        return ActionCache::resolveRouteFiles($package, $manager->path(), $routes);
    }
}

