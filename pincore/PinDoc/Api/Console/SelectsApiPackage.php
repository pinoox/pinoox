<?php

namespace Pinoox\PinDoc\Api\Console;

use Pinoox\PinDoc\Api\AppApiRegistry;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait SelectsApiPackage
{
    use SelectsPackage;

    protected function resolveApiPackage(InputInterface $input, OutputInterface $output, SymfonyStyle $io): string
    {
        return $this->resolvePackageFromCandidates($input, $output, $io, $this->apiPackages(), [
            'sectionTitle' => 'Apps with API routes',
            'emptyMessage' => 'No app with routes/api.php was found.',
            'invalidMessage' => "Package '%s' was not found or has no API routes.",
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function apiPackages(): array
    {
        $registry = new AppApiRegistry();
        $packages = [];

        foreach (AppEngine::all() as $package => $manager) {
            $entry = $registry->firstEntry($registry->all($package), $package);

            if ($entry === null || empty($entry['routes'])) {
                continue;
            }

            $packages[$package] = (string) ($manager->config()->get('name') ?: $package);
        }

        ksort($packages);

        return $packages;
    }

    protected function apiPackageExists(string $package): bool
    {
        return isset($this->apiPackages()[$package]);
    }
}

