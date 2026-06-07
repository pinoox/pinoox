<?php

namespace Pinoox\Component\Template\Frontend;

use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Path;
use Pinoox\Portal\View;
use Symfony\Component\Process\Process;

class ThemeFrontend
{
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

    public function build(bool $install = true): int
    {
        $this->assertFrontendProject();

        if ($install) {
            $this->runNpm(['ci'], fallback: ['install']);
        }

        return $this->runNpm(['run', 'build']);
    }

    public function dev(bool $install = true): int
    {
        $this->assertFrontendProject();

        if ($install && !is_dir($this->themePath . '/node_modules')) {
            $this->runNpm(['install']);
        }

        return $this->runNpm(['run', 'dev'], blocking: false);
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

    private function assertFrontendProject(): void
    {
        if (!$this->hasPackageJson()) {
            throw new \RuntimeException('package.json was not found in theme: ' . $this->themePath);
        }
    }

    /**
     * @param list<string> $command
     */
    private function runNpm(array $command, bool $blocking = true, ?array $fallback = null): int
    {
        $binary = $this->npmBinary();
        $process = new Process(array_merge([$binary], $command), $this->themePath, null, null, null);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful() && $fallback !== null) {
            $process = new Process(array_merge([$binary], $fallback), $this->themePath, null, null, null);
            $process->run(function ($type, $buffer) {
                echo $buffer;
            });
        }

        if ($blocking) {
            return (int) $process->getExitCode();
        }

        return 0;
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
