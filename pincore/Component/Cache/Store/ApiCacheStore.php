<?php

namespace Pinoox\Component\Cache\Store;

use Pinoox\Component\Cache\AppCacheConfig;
use Pinoox\Component\Cache\AppCacheFingerprint;
use Pinoox\Component\Cache\AppCacheManifest;
use Pinoox\Component\Cache\AppCachePath;
use Pinoox\Component\Cache\CacheStoreInterface;
use Pinoox\Component\Cache\PhpCacheFile;
use Pinoox\PinDoc\Api\AppApiRegistry;
use Pinoox\Portal\App\AppEngine;

class ApiCacheStore implements CacheStoreInterface
{
    public function name(): string
    {
        return 'api';
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
        $registry = new AppApiRegistry();
        $entries = $registry->all($package);

        AppCachePath::ensureDir($package);
        PhpCacheFile::write($this->path($package), [
            'entries' => $entries,
        ]);

        AppCacheManifest::touchStore($package, $this->name(), [
            'checksum' => AppCacheFingerprint::files($this->sourceFiles($package)),
            'count' => count($entries),
        ]);

        return true;
    }

    public function clear(string $package): void
    {
        PhpCacheFile::unlink($this->path($package));
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    public static function loadEntries(?string $package = null): ?array
    {
        if ($package === null || $package === '') {
            return null;
        }

        if (!AppCacheConfig::storeEnabled('api', $package)) {
            return null;
        }

        $store = new self();
        if (!$store->isFresh($package)) {
            return null;
        }

        $data = PhpCacheFile::read($store->path($package));
        if ($data === null) {
            return null;
        }

        if (isset($data['entries']) && is_array($data['entries'])) {
            return $data['entries'];
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(string $package): array
    {
        $manager = AppEngine::manager($package);
        $files = [];
        $routesDir = $manager->path('routes');
        $main = $routesDir . '/api.php';

        if (is_file($main)) {
            $files[] = str_replace('\\', '/', $main);
        }

        foreach (glob($routesDir . '/api-v*.php') ?: [] as $file) {
            $files[] = str_replace('\\', '/', $file);
        }

        $boot = $manager->path('boot.php');
        if (is_file($boot)) {
            $files[] = str_replace('\\', '/', $boot);
        }

        sort($files);

        return $files;
    }
}

