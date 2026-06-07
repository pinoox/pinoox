<?php

namespace Pinoox\Component\Package;

use Pinoox\Component\Cache\AppCacheManager;
use Pinoox\Component\Database\Patch\PatchToolkit;
use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Migration\Migrator;
use Pinoox\Component\Package\Engine\AppEngine;

/**
 * Provisions apps that already exist on disk (project setup / bulk bootstrap).
 * Mirrors post-extract steps from PinxInstaller: migrate, patch, cache, lang.
 */
final class AppProvisioner
{
    public function __construct(
        private readonly AppEngine $engine,
    ) {
    }

    /**
     * @param array{
     *     exclude?: list<string>,
     *     lang?: ?string,
     *     only_enabled?: bool,
     *     skip_migrate?: bool,
     *     skip_patch?: bool,
     *     skip_cache?: bool,
     *     force?: bool
     * } $options
     * @return list<string> Provisioned package names in dependency order
     */
    public function provisionInstalledApps(array $options = []): array
    {
        $exclude = array_values(array_filter(
            $options['exclude'] ?? [],
            static fn (mixed $package): bool => is_string($package) && $package !== '',
        ));
        $onlyEnabled = (bool) ($options['only_enabled'] ?? true);

        $packages = [];
        foreach ($this->engine->all() as $package => $manager) {
            if (in_array($package, $exclude, true)) {
                continue;
            }

            if ($onlyEnabled && !$manager->stable()) {
                continue;
            }

            $packages[] = $package;
        }

        $ordered = AppDependency::sortForInstall($packages, $this->engine);
        $provisioned = [];

        foreach ($ordered as $package) {
            $this->provision($package, $options);
            $provisioned[] = $package;
        }

        return $provisioned;
    }

    /**
     * @param array{
     *     lang?: ?string,
     *     skip_migrate?: bool,
     *     skip_patch?: bool,
     *     skip_cache?: bool,
     *     force?: bool
     * } $options
     */
    public function provision(string $package, array $options = []): void
    {
        if (!$this->engine->exists($package)) {
            throw new Exception('App package not found: ' . $package);
        }

        if (!($options['skip_migrate'] ?? false)) {
            (new Migrator($package))->run();
        }

        if (!($options['skip_patch'] ?? false)) {
            $this->runPatches($package, (bool) ($options['force'] ?? false));
        }

        if (!($options['skip_cache'] ?? false)) {
            AppCacheManager::build($package, null, true);
        }

        $this->applyDefaultLang($package, $options['lang'] ?? null);
    }

    private function applyDefaultLang(string $package, ?string $lang): void
    {
        if (!is_string($lang) || $lang === '') {
            return;
        }

        $manager = $this->engine->manager($package);

        if ($manager->lang()->existsLocale($lang)) {
            $manager->config()->set('lang', $lang)->save();
        }
    }

    private function runPatches(string $package, bool $force): void
    {
        $toolkit = new PatchToolkit();
        $toolkit->package($package)->load();

        if (!$toolkit->isSuccess()) {
            throw new Exception('Patch load failed for ' . $package . ': ' . $toolkit->getErrors());
        }

        foreach ($toolkit->getPatches() as $patch) {
            if ($patch['ran'] || !$patch['should_run']) {
                continue;
            }

            try {
                $startedAt = microtime(true);
                $patch['instance']->run();
                $toolkit->recordSuccess(
                    $patch['name'],
                    $patch['checksum'],
                    (int) round((microtime(true) - $startedAt) * 1000),
                );
            } catch (\Throwable $e) {
                if (!$force) {
                    throw new Exception(
                        'Patch failed for ' . $package . ': ' . $patch['name'] . ' - ' . $e->getMessage(),
                        previous: $e,
                    );
                }
            }
        }
    }
}
