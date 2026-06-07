<?php

namespace Pinoox\PinDoc\GraphQL\Console;

use Pinoox\PinDoc\GraphQL\GraphQLRegistry;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait SelectsGraphQLPackage
{
    use SelectsPackage;

    protected function resolveGraphQLPackage(InputInterface $input, OutputInterface $output, SymfonyStyle $io): string
    {
        return $this->resolvePackageFromCandidates($input, $output, $io, $this->graphQLPackages(), [
            'sectionTitle' => 'Apps with GraphQL definitions',
            'emptyMessage' => 'No app with routes/graphql.php was found.',
            'invalidMessage' => "Package '%s' was not found or has no GraphQL definitions.",
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function graphQLPackages(): array
    {
        $registry = new GraphQLRegistry();
        $packages = [];

        foreach (AppEngine::all() as $package => $manager) {
            $entry = $registry->all($package)[$package] ?? null;

            if ($entry === null) {
                continue;
            }

            if (empty($entry['queries']) && empty($entry['mutations'])) {
                continue;
            }

            $packages[$package] = (string) ($manager->config()->get('name') ?: $package);
        }

        ksort($packages);

        return $packages;
    }

    protected function graphQLPackageExists(string $package): bool
    {
        return isset($this->graphQLPackages()[$package]);
    }
}

