<?php

namespace Pinoox\Terminal\Pinker;

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Store\Baker\Pinker;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Pinker as PinkerPortal;
use Pinoox\Support\SystemConfig;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait PinkerCommandSupport
{
    use SelectsPackage;

    protected function resolvePinkerPackage(InputInterface $input, OutputInterface $output, SymfonyStyle $io, bool $allowAll = true): string
    {
        return $this->resolvePackageRequired($input, $output, $io, [
            'allowAll' => $allowAll,
            'default' => $allowAll ? 'all' : 'platform',
            'sectionTitle' => 'Pinker packages',
        ]);
    }

    protected function pinkerPackageExists(string $package): bool
    {
        return $this->packageExists($package, allowAll: true);
    }

    protected function pinkerPackages(): array
    {
        return $this->packageChoices();
    }

    protected function pinkerPackageRows(array $packages): array
    {
        return $this->packageRows($packages);
    }

    /**
     * @return array<string, array{package:string, label:string, source:string, pinker:Pinker}>
     */
    protected function pinkerEntries(string $package = 'all'): array
    {
        $entries = [];
        $packages = $package === 'all' ? array_keys($this->pinkerPackages()) : [$package];

        foreach ($packages as $pkg) {
            foreach ($this->sourceFiles($pkg) as $label => $sourceFile) {
                $pinker = new Pinker($sourceFile, PinkerPortal::bakedFileFromSource($sourceFile));

                if (basename($sourceFile) === SystemConfig::rawPath('app_file', 'app.php')) {
                    $pinker->dumping(true);
                }

                $entries[$pkg . ':' . $label] = [
                    'package' => $pkg,
                    'label' => $label,
                    'source' => $sourceFile,
                    'pinker' => $pinker,
                ];
            }
        }

        return $entries;
    }

    /**
     * @return array<string, string>
     */
    protected function sourceFiles(string $package): array
    {
        $base = $this->packagePath($package);
        $files = [];

        $appFile = SystemConfig::rawPath('app_file', 'app.php');
        if ($package !== 'platform' && is_file($base . '/' . $appFile)) {
            $files[$appFile] = $base . '/' . $appFile;
        }

        $configFolder = trim(SystemConfig::rawPath('app_config', 'config'), '/\\');
        $configPath = $base . '/' . $configFolder;

        if (is_dir($configPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($configPath, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $source = str_replace('\\', '/', $file->getPathname());
                $files[$configFolder . '/' . ltrim(substr($source, strlen(str_replace('\\', '/', $configPath))), '/')] = $source;
            }
        }

        ksort($files);

        return $files;
    }

    protected function packagePath(string $package): string
    {
        if ($package === 'platform') {
            return rtrim(str_replace('\\', '/', \PINOOX_CORE_PATH), '/');
        }

        return rtrim(str_replace('\\', '/', AppEngine::path($package)), '/');
    }

    protected function relativePath(?string $path): string
    {
        if ($path === null || $path === '') {
            return '-';
        }

        $base = rtrim(str_replace('\\', '/', (string)Loader::getBasePath()), '/') . '/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }

    protected function formatTime(mixed $time): string
    {
        return is_numeric($time) ? date('Y-m-d H:i:s', (int)$time) : '-';
    }
}

