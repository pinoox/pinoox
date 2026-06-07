<?php

namespace Pinoox\Component\Package\Pinx;

use Pinoox\Component\Cache\AppCacheManager;
use Pinoox\Component\Database\Patch\PatchToolkit;
use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Migration\Migrator;
use Pinoox\Component\Package\AppDependency;
use Pinoox\Component\Package\Engine\AppEngine;
use Pinoox\Component\Template\Theme\ThemeManifest;
use Pinoox\Portal\App\AppEngine as AppEnginePortal;
use Pinoox\Portal\FileSystem;
use Pinoox\Support\AppRegistry;
use Pinoox\Support\SystemConfig;

class PinxInstaller
{
    /** @var callable|null */
    private $stepListener = null;

    public function __construct(
        private AppEngine $engine,
        private string $tmpPath,
    ) {
    }

    /**
     * @param callable(string $step, string $status, string $message): void|null $listener
     */
    public function onStep(?callable $listener): self
    {
        $this->stepListener = $listener;

        return $this;
    }

    /**
     * @param array{
     *     force?: bool,
     *     skip_migrate?: bool,
     *     skip_patch?: bool,
     *     skip_cache?: bool,
     *     skip_verify?: bool,
     *     require_signature?: bool
     * } $options
     */
    public function install(string $packagePath, array $options = []): PinxInstallResult
    {
        $steps = [];
        $reader = new PinxReader();

        try {
            $reader->open($packagePath);
            $manifest = $reader->manifest();
            $zip = $reader->zip();

            $this->recordStep($steps, 'validate', 'ok', 'Package structure validated.');

            $verifyOptions = [
                'skip_verify' => (bool) ($options['skip_verify'] ?? false),
                'require_signature' => $this->requiresSignature($manifest, $options),
            ];

            $signature = PinxVerifier::verify(
                $zip,
                $manifest,
                $reader->manifestJson(),
                $verifyOptions,
            );

            if ($signature !== null) {
                $this->recordStep(
                    $steps,
                    'signature',
                    'ok',
                    'Signature verified for publisher ' . ($signature['key_id'] ?? 'unknown') . '.',
                );
            } else {
                $this->recordStep($steps, 'signature', 'skipped', 'Package is unsigned.');
            }

            if (!PinxVersion::satisfiesMinpin($manifest->minpin())) {
                throw new Exception(PinxVersion::minpinError($manifest->minpin()));
            }
            $this->recordStep($steps, 'minpin', 'ok', 'Minimum Pinoox version satisfied.');

            $depends = $manifest->depends();
            if ($depends !== []) {
                AppDependency::assertSatisfied($depends, $this->engine);
                $this->recordStep(
                    $steps,
                    'depends',
                    'ok',
                    'Required apps satisfied: ' . implode(', ', AppDependency::packages($depends)) . '.',
                );
            } else {
                $this->recordStep($steps, 'depends', 'skipped', 'No app dependencies declared.');
            }

            if ($manifest->isApp() && !$this->engine->checkName($manifest->package())) {
                throw new Exception('Invalid package name: ' . $manifest->package());
            }

            $mode = $this->detectMode($manifest, (bool) ($options['force'] ?? false));
            $this->recordStep($steps, 'detect', 'ok', ucfirst($mode) . ' detected.');

            if ($manifest->isTheme()) {
                $this->assertThemeTarget($manifest);
                $this->recordStep($steps, 'target', 'ok', 'Target app "' . $manifest->targetApp() . '" is available.');
            }

            if ($mode === 'update' && !$this->canUpdate($manifest, (bool) ($options['force'] ?? false))) {
                throw new Exception('Installed version is newer than package version.');
            }

            $destination = $this->destinationPath($manifest);

            if ($signature !== null && $mode === 'update') {
                PinxIdentity::assertUpdateAllowed($destination, $manifest, $signature);
                $this->recordStep($steps, 'identity', 'ok', 'Publisher identity matches installed app.');
            } elseif ($signature !== null) {
                $this->recordStep($steps, 'identity', 'skipped', 'Fresh install publisher recorded after extraction.');
            }

            $this->extractPackage($manifest, $zip, $destination);
            $this->recordStep($steps, 'extract', 'ok', 'Files extracted to ' . $destination . '.');

            if ($manifest->isTheme()) {
                $this->assertThemeManifest($manifest, $destination);
                $this->recordStep($steps, 'theme_meta', 'ok', 'Theme manifest validated.');
            }

            if ($signature !== null) {
                PinxIdentity::write($destination, $signature, $manifest->toArray());
            }

            AppEnginePortal::__rebuild();

            if ($manifest->isApp()) {
                $this->runRegistry($manifest, $steps);

                if (!($options['skip_migrate'] ?? false)) {
                    $this->runMigrate($manifest->package(), $steps, (bool) ($options['force'] ?? false));
                } else {
                    $this->recordStep($steps, 'migrate', 'skipped', 'Migration skipped by option.');
                }

                if (!($options['skip_patch'] ?? false)) {
                    $this->runPatches($manifest->package(), $steps, (bool) ($options['force'] ?? false));
                } else {
                    $this->recordStep($steps, 'patch', 'skipped', 'Patches skipped by option.');
                }

                if (!($options['skip_cache'] ?? false)) {
                    $this->rebuildCache($manifest->package(), $steps);
                } else {
                    $this->recordStep($steps, 'cache', 'skipped', 'Cache rebuild skipped by option.');
                }
            } else {
                $this->recordStep($steps, 'registry', 'skipped', 'Registry not required for theme packages.');
                $this->recordStep($steps, 'migrate', 'skipped', 'Migration not applicable for theme packages.');
                $this->recordStep($steps, 'patch', 'skipped', 'Patches not applicable for theme packages.');
                if (!($options['skip_cache'] ?? false)) {
                    $this->rebuildCache($manifest->targetApp(), $steps);
                }
            }

            $message = $manifest->isApp()
                ? sprintf('App "%s" %s successfully.', $manifest->package(), $mode === 'update' ? 'updated' : 'installed')
                : sprintf('Theme "%s" installed into "%s".', $manifest->themeName(), $manifest->targetApp());

            $this->recordStep($steps, 'complete', 'ok', $message);

            return new PinxInstallResult(true, $mode, $manifest, $steps, $message);
        } catch (\Throwable $e) {
            $failedManifest = PinxManifest::fromArray([]);
            try {
                $failedManifest = $reader->manifest();
            } catch (\Throwable) {
            }

            $this->recordStep($steps, 'failed', 'error', $e->getMessage());

            return new PinxInstallResult(false, 'failed', $failedManifest, $steps, $e->getMessage());
        } finally {
            $reader->close();
            $this->cleanupTemp($packagePath);
        }
    }

    /**
     * @param array{require_signature?: bool} $options
     */
    private function requiresSignature(PinxManifest $manifest, array $options): bool
    {
        if (!empty($options['require_signature'])) {
            return true;
        }

        if (PinxSignConfig::system()['require_signature']) {
            return true;
        }

        if (!$this->engine->exists($manifest->package())) {
            return false;
        }

        try {
            $pinx = $this->engine->config($manifest->package())->get('pinx') ?? [];

            return PinxSignConfig::app(is_array($pinx) ? $pinx : [])['require_signature'];
        } catch (\Throwable) {
            return false;
        }
    }

    private function detectMode(PinxManifest $manifest, bool $force): string
    {
        if ($manifest->isTheme()) {
            $themePath = $this->engine->path($manifest->targetApp(), 'theme/' . $manifest->themeName());
            return is_dir($themePath) ? 'update' : 'install';
        }

        if (!$this->engine->exists($manifest->package())) {
            return 'install';
        }

        return 'update';
    }

    private function canUpdate(PinxManifest $manifest, bool $force): bool
    {
        if ($force) {
            return true;
        }

        if ($manifest->isTheme()) {
            return true;
        }

        $existing = include $this->engine->path($manifest->package(), 'app.php');
        $existingCode = (int) ($existing['version-code'] ?? 0);

        return $existingCode <= $manifest->versionCode();
    }

    private function assertThemeTarget(PinxManifest $manifest): void
    {
        if (!$this->engine->exists($manifest->targetApp())) {
            throw new Exception('Target app not found: ' . $manifest->targetApp());
        }
    }

    private function assertThemeManifest(PinxManifest $manifest, string $themePath): void
    {
        $themeManifest = ThemeManifest::fromPath($themePath, $manifest->targetApp(), $manifest->themeName());

        if ($themeManifest === null || !ThemeManifest::hasManifest($themePath)) {
            throw new Exception('Theme folder is missing theme.php: ' . $manifest->themeName());
        }

        $themeManifest->validate($manifest->targetApp());

        if ($themeManifest->name() !== $manifest->themeName()) {
            throw new Exception(sprintf(
                'Theme meta name "%s" does not match package theme "%s".',
                $themeManifest->name(),
                $manifest->themeName(),
            ));
        }
    }

    private function destinationPath(PinxManifest $manifest): string
    {
        if ($manifest->isTheme()) {
            return $this->engine->path($manifest->targetApp(), 'theme/' . $manifest->themeName());
        }

        return $this->engine->path($manifest->package());
    }

    private function extractPackage(PinxManifest $manifest, $zip, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        if ($manifest->isLegacy()) {
            $zip->extractTo($destination)->deleteFromRegex('~^\..~');
            return;
        }

        $prefix = PinxManifest::PAYLOAD_PREFIX;
        $entries = [];

        foreach ($zip->getListFiles() as $entry) {
            if (!str_starts_with($entry, $prefix)) {
                continue;
            }

            $relative = substr($entry, strlen($prefix));
            if ($manifest->isTheme()) {
                $themePrefix = 'theme/' . $manifest->themeName() . '/';
                if (!str_starts_with($relative, $themePrefix)) {
                    continue;
                }
                $relative = substr($relative, strlen($themePrefix));
            }

            $entries[] = [
                'source' => $entry,
                'target' => rtrim($destination, '/\\') . '/' . ltrim($relative, '/\\'),
            ];
        }

        if ($entries === []) {
            throw new Exception('No payload files found in pinx archive.');
        }

        foreach ($entries as $item) {
            $targetDir = dirname($item['target']);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            file_put_contents($item['target'], $zip->getEntryContents($item['source']));
        }
    }

    /**
     * @param list<array{step: string, status: string, message: string}> $steps
     */
    private function runRegistry(PinxManifest $manifest, array &$steps): void
    {
        $registryFile = SystemConfig::path('system_registry');
        if (!is_file($registryFile)) {
            $this->recordStep($steps, 'registry', 'skipped', 'System registry file not found.');
            return;
        }

        $config = require $registryFile;
        if (!is_array($config)) {
            $this->recordStep($steps, 'registry', 'skipped', 'System registry is empty.');
            return;
        }

        $packages = $config['packages'] ?? [];
        if (!is_array($packages) || !array_key_exists($manifest->package(), $packages)) {
            $this->recordStep($steps, 'registry', 'skipped', 'Package uses auto-discovery.');
            return;
        }

        AppRegistry::load($registryFile, dirname($registryFile, 3));
        $this->recordStep($steps, 'registry', 'ok', 'Registry entry verified for ' . $manifest->package() . '.');
    }

    /**
     * @param list<array{step: string, status: string, message: string}> $steps
     */
    private function runMigrate(string $package, array &$steps, bool $force = false): void
    {
        try {
            (new Migrator('platform'))->run();
            $this->runPatches('platform', $steps, $force);
            (new Migrator($package))->run();
            $this->recordStep($steps, 'migrate', 'ok', 'Migrations completed for ' . $package . '.');
        } catch (\Throwable $e) {
            throw new Exception('Migration failed: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * @param list<array{step: string, status: string, message: string}> $steps
     */
    private function runPatches(string $package, array &$steps, bool $force): void
    {
        $toolkit = new PatchToolkit();
        $toolkit->package($package)->load();

        if (!$toolkit->isSuccess()) {
            throw new Exception('Patch load failed: ' . $toolkit->getErrors());
        }

        $patches = $toolkit->getPatches();
        if ($patches === []) {
            $this->recordStep($steps, 'patch', 'skipped', 'No patches found.');
            return;
        }

        $executed = 0;
        foreach ($patches as $patch) {
            if ($patch['ran'] || !$patch['should_run']) {
                continue;
            }

            try {
                $startedAt = microtime(true);
                $patch['instance']->run();
                $toolkit->recordSuccess(
                    $patch['name'],
                    $patch['checksum'],
                    (int) round((microtime(true) - $startedAt) * 1000)
                );
                $executed++;
            } catch (\Throwable $e) {
                if (!$force) {
                    throw new Exception('Patch failed: ' . $patch['name'] . ' - ' . $e->getMessage(), previous: $e);
                }
            }
        }

        $this->recordStep($steps, 'patch', 'ok', $executed . ' patch(es) executed.');
    }

    /**
     * @param list<array{step: string, status: string, message: string}> $steps
     */
    private function rebuildCache(string $package, array &$steps): void
    {
        AppCacheManager::build($package, null, true);
        $this->recordStep($steps, 'cache', 'ok', 'Runtime cache rebuilt for ' . $package . '.');
    }

    /**
     * @param list<array{step: string, status: string, message: string}> $steps
     */
    private function recordStep(array &$steps, string $step, string $status, string $message): void
    {
        $entry = [
            'step' => $step,
            'status' => $status,
            'message' => $message,
        ];
        $steps[] = $entry;

        if ($this->stepListener !== null) {
            ($this->stepListener)($step, $status, $message);
        }
    }

    private function cleanupTemp(string $packagePath): void
    {
        $base = basename($packagePath);
        $base = preg_replace('/\.(pinx|pin)$/i', '', $base) ?: $base;
        $tmp = rtrim($this->tmpPath, '/\\') . '/' . $base;

        if (is_dir($tmp)) {
            FileSystem::remove($tmp);
        }
    }
}

