<?php

namespace Pinoox\Component\Package;

use Pinoox\Component\Cache\AppCacheManager;
use Pinoox\Component\Database\Patch\PatchToolkit;
use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Migration\Migrator;
use Pinoox\Component\Package\AppDependency;
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
        $packages = $this->packagesForSetup($options);
        $provisioned = [];

        foreach ($packages as $package) {
            $this->provision($package, $options);
            $provisioned[] = $package;
        }

        return $provisioned;
    }

    /**
     * Enabled on-disk apps for project setup (dependency order, no installer).
     *
     * @param array{
     *     exclude?: list<string>,
     *     only_enabled?: bool
     * } $options
     * @return list<string>
     */
    public function packagesForSetup(array $options = []): array
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
        $this->assertDependencies($ordered);

        return $ordered;
    }

    /**
     * @param list<string> $packages
     */
    public function migratePackages(array $packages): void
    {
        foreach ($packages as $package) {
            if (!$this->hasMigrationFiles($package)) {
                continue;
            }

            (new Migrator($package))->run();
        }
    }

    /**
     * @param list<string> $packages
     */
    public function patchPackages(array $packages, bool $force = false): void
    {
        foreach ($packages as $package) {
            $this->runPatches($package, $force);
        }
    }

    /**
     * @param list<string> $packages
     */
    public function applyLangToPackages(array $packages, ?string $lang): void
    {
        foreach ($packages as $package) {
            $this->applyDefaultLang($package, $lang);
        }
    }

    /**
     * Bootstrap pincore after database credentials are available.
     *
     * Runs core migrations and system patches (system/patches).
     * Mirrors the pincore half of pinx:install and web installer installCore.
     *
     * @param array{
     *     skip_migrate?: bool,
     *     skip_patch?: bool,
     *     force?: bool
     * } $options
     */
    public function provisionCore(array $options = []): void
    {
        if (!($options['skip_migrate'] ?? false)) {
            (new Migrator('platform', 'run'))->run();
        }

        if (!($options['skip_patch'] ?? false)) {
            $this->runPatches('platform', (bool) ($options['force'] ?? false));
        }
    }

    /**
     * @param list<string> $packages
     */
    private function assertDependencies(array $packages): void
    {
        foreach ($packages as $package) {
            if (!$this->engine->exists($package)) {
                continue;
            }

            $appFile = $this->engine->path($package, 'app.php');
            $config = is_file($appFile) ? include $appFile : [];

            if (!is_array($config)) {
                continue;
            }

            AppDependency::assertSatisfied(AppDependency::fromAppConfig($config), $this->engine);
        }
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

    private function hasMigrationFiles(string $package): bool
    {
        if (!$this->engine->exists($package)) {
            return false;
        }

        $folder = trim((string) \Pinoox\Support\SystemConfig::rawPath('app_migrations', 'database/migrations'), '/\\');
        $path = $this->engine->path($package) . '/' . $folder;

        if (!is_dir($path)) {
            return false;
        }

        foreach (glob($path . '/*.php') ?: [] as $file) {
            if (is_file($file)) {
                return true;
            }
        }

        return false;
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

