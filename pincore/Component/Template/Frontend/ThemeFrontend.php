<?php

namespace Pinoox\Component\Template\Frontend;

use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Path;
use Symfony\Component\Process\Process;

class ThemeFrontend
{
    public const INSTALL_SKIP = 'skip';
    public const INSTALL_SMART = 'smart';
    public const INSTALL_FORCE = 'force';

    /** @var callable(string): void|null */
    private $outputWriter = null;

    public function __construct(
        private readonly string $package,
        private readonly string $themePath,
        private readonly array $config,
    ) {
    }

    public static function forPackage(?string $package = null): self
    {
        $package = $package ?: App::package();
        $stack = \Pinoox\Component\Template\Theme\ThemeStack::resolve($package);
        $themeName = $stack['name'];
        $themePath = $stack['paths'][0] ?? rtrim(str_replace('\\', '/', Path::get(App::get('path-theme') . '/' . $themeName)), '/');

        if ($package !== App::package() && AppEngine::exists($package)) {
            $stack = \Pinoox\Component\Template\Theme\ThemeStack::resolve($package);
            $themeName = $stack['name'];
            $themePath = $stack['paths'][0] ?? rtrim(str_replace('\\', '/', AppEngine::path($package) . '/theme/' . $themeName), '/');
        }

        return new self($package, $themePath, FrontendConfig::forThemePath($themePath));
    }

    public static function forPackageAndTheme(string $package, string $themeName): self
    {
        $themePath = rtrim(str_replace('\\', '/', AppEngine::path($package) . '/theme/' . $themeName), '/');

        return new self($package, $themePath, FrontendConfig::forThemePath($themePath));
    }

    /**
     * @return array<string, string> folder => details label
     */
    public static function listThemeFolders(string $package): array
    {
        if (!AppEngine::exists($package)) {
            return [];
        }

        $root = rtrim(str_replace('\\', '/', AppEngine::path($package) . '/theme'), '/');
        if (!is_dir($root)) {
            return [];
        }

        $defaultTheme = (string) AppEngine::config($package)->get('theme', 'default');
        $themes = [];

        foreach (scandir($root) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $root . '/' . $entry;
            if (!is_dir($path)) {
                continue;
            }

            $hasManifest = is_file($path . '/theme.php');
            $hasPackageJson = is_file($path . '/package.json');
            $hasFrontendConfig = is_file($path . '/frontend.config.php');

            if (!$hasManifest && !$hasPackageJson && !$hasFrontendConfig) {
                continue;
            }

            $parts = [];
            if ($hasPackageJson) {
                $parts[] = 'vite';
            }
            if ($hasManifest) {
                $parts[] = 'manifest';
            }
            if ($entry === $defaultTheme) {
                $parts[] = 'default';
            }

            $themes[$entry] = $parts === [] ? $entry : implode(', ', $parts);
        }

        ksort($themes);

        return $themes;
    }

    /**
     * Find app packages that contain a theme folder with the given name.
     *
     * @return array<string, string> package => app display name
     */
    public static function findPackagesByThemeFolder(string $themeName): array
    {
        $themeName = trim($themeName);
        if ($themeName === '') {
            return [];
        }

        $matches = [];

        foreach (AppEngine::all() as $package => $manager) {
            $themes = self::listThemeFolders($package);
            if (!isset($themes[$themeName])) {
                continue;
            }

            $matches[$package] = (string) ($manager->config()->get('name') ?: $package);
        }

        ksort($matches);

        return $matches;
    }

    /**
     * @param callable(string): void $writer
     */
    public function setOutputWriter(callable $writer): void
    {
        $this->outputWriter = $writer;
    }

    public function package(): string
    {
        return $this->package;
    }

    public function themePath(): string
    {
        return $this->themePath;
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    public function hasPackageJson(): bool
    {
        return is_file($this->themePath . '/package.json');
    }

    public function manifestPath(): string
    {
        return $this->themePath . '/' . ltrim((string) ($this->config['manifest'] ?? 'dist/.vite/manifest.json'), '/');
    }

    public function manifestExists(): bool
    {
        return is_file($this->manifestPath());
    }

    public function needsNpmInstall(): bool
    {
        $nodeModules = $this->themePath . '/node_modules';
        if (!is_dir($nodeModules)) {
            return true;
        }

        $stamp = $this->nodeModulesStamp($nodeModules);

        foreach (['package-lock.json', 'npm-shrinkwrap.json', 'package.json'] as $file) {
            $path = $this->themePath . '/' . $file;
            if (is_file($path) && filemtime($path) > $stamp) {
                return true;
            }
        }

        return false;
    }

    public function install(): int
    {
        $this->assertFrontendProject();

        return $this->runNpmInstall();
    }

    public function build(string $installMode = self::INSTALL_SKIP): int
    {
        $this->assertFrontendProject();
        $this->ensureDependencies($installMode);

        return $this->runNpm(['run', 'build']);
    }

    public function dev(string $installMode = self::INSTALL_SKIP): int
    {
        $this->assertFrontendProject();
        $this->ensureDependencies($installMode);

        return $this->runNpm(['run', 'dev'], longRunning: true);
    }

    public function runScript(string $script, string $installMode = self::INSTALL_SKIP): int
    {
        $scripts = $this->npmScripts();
        if (!isset($scripts[$script])) {
            throw new \InvalidArgumentException(sprintf(
                'npm script "%s" was not found. Available: %s',
                $script,
                $scripts === [] ? '(none)' : implode(', ', array_keys($scripts)),
            ));
        }

        $this->assertFrontendProject();
        $this->ensureDependencies($installMode);

        return $this->runNpm(['run', $script], longRunning: $this->isLongRunningScript($script));
    }

    /**
     * @return array<string, string>
     */
    public function npmScripts(): array
    {
        $path = $this->themePath . '/package.json';
        if (!is_file($path)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (!is_array($json) || !isset($json['scripts']) || !is_array($json['scripts'])) {
            return [];
        }

        $scripts = [];
        foreach ($json['scripts'] as $name => $command) {
            if (is_string($name) && (is_string($command) || is_numeric($command))) {
                $scripts[$name] = (string) $command;
            }
        }

        ksort($scripts);

        return $scripts;
    }

    /**
     * @return array<string, mixed>
     */
    public function info(): array
    {
        return [
            'package' => $this->package,
            'theme_path' => $this->themePath,
            'stack' => $this->config['stack'] ?? 'twig',
            'entry' => $this->config['entry'] ?? null,
            'manifest' => $this->manifestPath(),
            'manifest_exists' => $this->manifestExists(),
            'package_json' => $this->hasPackageJson(),
            'dev_enabled' => FrontendConfig::isDevEnabled($this->config),
            'dev_url' => $this->config['dev']['url'] ?? null,
            'npm_scripts' => $this->npmScripts(),
            'node_modules' => is_dir($this->themePath . '/node_modules'),
            'needs_npm_install' => $this->hasPackageJson() && $this->needsNpmInstall(),
        ];
    }

    public function scaffold(string $stack): void
    {
        $stack = strtolower(trim($stack));
        $corePath = defined('PINOOX_CORE_PATH')
            ? rtrim(str_replace('\\', '/', (string) PINOOX_CORE_PATH), '/')
            : dirname(__DIR__, 3);
        $stubRoot = $corePath . '/stubs/frontend/' . $stack;

        if (!is_dir($stubRoot)) {
            throw new \InvalidArgumentException(sprintf('Frontend stack stub "%s" was not found.', $stack));
        }

        if (!is_dir($this->themePath)) {
            mkdir($this->themePath, 0777, true);
        }

        $this->copyStubTree($stubRoot, $this->themePath);
    }

    private function ensureDependencies(string $installMode): void
    {
        if ($installMode === self::INSTALL_SKIP) {
            return;
        }

        if ($installMode === self::INSTALL_FORCE || $this->needsNpmInstall()) {
            $this->runNpmInstall();
        }
    }

    private function runNpmInstall(): int
    {
        $lock = $this->themePath . '/package-lock.json';
        if (is_file($lock)) {
            return $this->runNpm(['ci'], fallback: ['install']);
        }

        return $this->runNpm(['install']);
    }

    private function assertFrontendProject(): void
    {
        if (!$this->hasPackageJson()) {
            throw new \RuntimeException('package.json was not found in theme: ' . $this->themePath);
        }
    }

    private function nodeModulesStamp(string $nodeModules): int
    {
        $lockStamp = $nodeModules . '/.package-lock.json';
        if (is_file($lockStamp)) {
            return (int) filemtime($lockStamp);
        }

        return (int) filemtime($nodeModules);
    }

    private function isLongRunningScript(string $script): bool
    {
        if (in_array($script, ['dev', 'preview', 'serve', 'start'], true)) {
            return true;
        }

        $command = strtolower($this->npmScripts()[$script] ?? '');

        return str_contains($command, 'vite')
            && !str_contains($command, 'build');
    }

    /**
     * @param list<string> $command
     */
    private function runNpm(array $command, bool $longRunning = false, ?array $fallback = null): int
    {
        $binary = $this->npmBinary();
        $process = new Process(array_merge([$binary], $command), $this->themePath, null, null, null);
        $process->run(function ($type, $buffer) {
            $this->emit($buffer);
        });

        if (!$process->isSuccessful() && $fallback !== null) {
            $process = new Process(array_merge([$binary], $fallback), $this->themePath, null, null, null);
            $process->run(function ($type, $buffer) {
                $this->emit($buffer);
            });
        }

        if ($longRunning) {
            return (int) ($process->getExitCode() ?? 0);
        }

        return (int) $process->getExitCode();
    }

    private function emit(string $buffer): void
    {
        if ($this->outputWriter !== null) {
            ($this->outputWriter)($buffer);

            return;
        }

        echo $buffer;
    }

    private function npmBinary(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm';
    }

    private function copyStubTree(string $source, string $destination): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $target = $destination . '/' . $iterator->getSubPathname();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0777, true);
                }
                continue;
            }

            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }

            copy($item->getPathname(), $target);
        }
    }
}
