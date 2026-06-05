<?php

namespace Pinoox\Terminal\Pincore;

use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\FileSystem;
use Pinoox\Support\SystemApp;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'cache:clear',
    description: 'Clear Pinker (stub/config) cache for all packages'
)]
class CacheClearCommand extends Terminal
{
    protected function configure(): void
    {
        // No arguments needed by default; you can optionally pass a package name
        $this->addArgument('package', InputArgument::OPTIONAL, 'Optional package name to clear only one package');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $package = $input->getArgument('package');

        // Determine packages to clear
        if (!empty($package)) {
            $packages = [$package];
        } else {
            // Get all registered apps including core
            $packages = ['pincore', ...array_keys(AppEngine::all())];
        }

        foreach ($packages as $pkg) {
            foreach ($this->pinkerDirs($pkg) as $pinkerDir) {
                FileSystem::remove($pinkerDir);
            }

            $output->writeln("<info>Pinker cache cleared for [{$pkg}]</info>");
        }

        return Command::SUCCESS;
    }

    private function pinkerDirs(string $package): array
    {
        if ($package === 'pincore') {
            return [
                path('~/pinker/pincore'),
                path('~/pinker/system'),
            ];
        }

        return [path('~/pinker/apps/' . $package)];
    }
} 
