<?php

namespace Pinoox\Component\Cache\Store;

use Pinoox\Component\AppEvent\AppApiRegistryStore;
use Pinoox\Component\AppEvent\AppBootstrap;
use Pinoox\Component\AppEvent\AppGraphQLRegistryStore;
use Pinoox\Component\Cache\AppCacheConfig;
use Pinoox\Component\Cache\AppCacheFingerprint;
use Pinoox\Component\Cache\AppCacheManifest;
use Pinoox\Component\Cache\AppCachePath;
use Pinoox\Component\Cache\CacheStoreInterface;
use Pinoox\Component\Cache\PhpCacheFile;
use Pinoox\Component\Kernel\Container\AppServiceContainer;
use Pinoox\Component\Kernel\Container\ControllerAutowirer;
use Pinoox\Component\Kernel\Container\ServiceContainerBootstrap;
use Pinoox\Component\Kernel\Container as KernelContainer;
use Pinoox\Portal\App\AppEngine;

class BootCacheStore implements CacheStoreInterface
{
    public function name(): string
    {
        return 'boot';
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
        AppBootstrap::ensure($package, false);

        $payload = [
            'api_manifests' => AppApiRegistryStore::export($package),
            'graphql_manifests' => AppGraphQLRegistryStore::export($package),
        ];

        if (ServiceContainerBootstrap::containerEnabled($package)) {
            $builder = KernelContainer::app($package);
            AppServiceContainer::register($builder, $package);
            $payload['container'] = AppServiceContainer::export($package);
        }

        AppCachePath::ensureDir($package);
        PhpCacheFile::write($this->path($package), $payload);

        AppCacheManifest::touchStore($package, $this->name(), [
            'checksum' => AppCacheFingerprint::files($this->sourceFiles($package)),
        ]);

        return true;
    }

    public function clear(string $package): void
    {
        PhpCacheFile::unlink($this->path($package));
    }

    public static function tryHydrate(string $package): bool
    {
        if (!AppCacheConfig::storeEnabled('boot', $package)) {
            return false;
        }

        $store = new self();
        if (!$store->isFresh($package)) {
            return false;
        }

        $data = PhpCacheFile::read($store->path($package));
        if ($data === null) {
            return false;
        }

        AppApiRegistryStore::import($package, (array) ($data['api_manifests'] ?? []));
        AppGraphQLRegistryStore::import($package, (array) ($data['graphql_manifests'] ?? []));

        if (is_array($data['container'] ?? null)) {
            $builder = KernelContainer::app($package);
            ControllerAutowirer::rebuildFromCache($package, $data['container'], $builder);
        }

        return AppApiRegistryStore::has($package) || AppGraphQLRegistryStore::has($package) || isset($data['container']);
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(string $package): array
    {
        $manager = AppEngine::manager($package);
        $files = [];

        $boot = $manager->path('boot.php');
        if (is_file($boot)) {
            $files[] = str_replace('\\', '/', $boot);
        }

        try {
            $bootConfig = $manager->config()->get('boot');
            if (is_string($bootConfig) && $bootConfig !== '' && $bootConfig !== 'boot.php') {
                $custom = $manager->path($bootConfig);
                if (is_file($custom)) {
                    $files[] = str_replace('\\', '/', $custom);
                }
            }
        } catch (\Throwable) {
        }

        $bindings = AppEngine::path($package, 'bindings.php');
        if (is_file($bindings)) {
            $files[] = str_replace('\\', '/', $bindings);
        }

        $appFile = AppEngine::path($package, 'app.php');
        if (is_file($appFile)) {
            $files[] = str_replace('\\', '/', $appFile);
        }

        $controllerDir = AppEngine::path($package, 'Controller');
        if (is_dir($controllerDir)) {
            $finder = new \Symfony\Component\Finder\Finder();
            $finder->files()->in($controllerDir)->name('*.php');
            foreach ($finder as $file) {
                $files[] = str_replace('\\', '/', $file->getRealPath());
            }
        }

        sort($files);

        return $files;
    }
}

