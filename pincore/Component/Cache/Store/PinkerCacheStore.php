<?php

namespace Pinoox\Component\Cache\Store;

use Pinoox\Component\Cache\AppCacheManifest;
use Pinoox\Component\Cache\AppCachePath;
use Pinoox\Component\Cache\CacheStoreInterface;
use Pinoox\Component\Store\Baker\Pinker;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Pinker as PinkerPortal;
use Pinoox\Support\SystemConfig;

class PinkerCacheStore implements CacheStoreInterface
{
    public function name(): string
    {
        return 'pinker';
    }

    public function path(string $package): string
    {
        if ($package === 'platform') {
            return rtrim(str_replace('\\', '/', SystemConfig::path('pinker')), '/') . '/pincore';
        }

        return rtrim(str_replace('\\', '/', SystemConfig::path('pinker')), '/')
            . '/apps/' . $package;
    }

    public function isFresh(string $package): bool
    {
        return is_dir($this->path($package));
    }

    public function build(string $package): bool
    {
        foreach ($this->entries($package) as $entry) {
            $entry['pinker']->rebuild();
        }

        AppCacheManifest::touchStore($package, $this->name(), [
            'checksum' => sha1($package . ':' . time()),
        ]);

        return true;
    }

    public function clear(string $package): void
    {
        foreach ($this->entries($package) as $entry) {
            $entry['pinker']->removeCache();
        }
    }

    /**
     * @return list<array{pinker: Pinker}>
     */
    private function entries(string $package): array
    {
        $entries = [];

        if ($package === 'platform') {
            $base = rtrim(str_replace('\\', '/', \PINOOX_CORE_PATH), '/');
        } elseif (!AppEngine::exists($package)) {
            return $entries;
        } else {
            $base = AppEngine::path($package);
        }

        $appFile = SystemConfig::rawPath('app_file', 'app.php');
        $source = $base . '/' . $appFile;

        if ($package !== 'platform' && is_file($source)) {
            $entries[] = [
                'pinker' => (new Pinker($source, PinkerPortal::bakedFileFromSource($source)))->dumping(true),
            ];
        }

        return $entries;
    }
}

