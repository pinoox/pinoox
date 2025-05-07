<?php

namespace Pinoox\Terminal\Pincore;

use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
        $this->addArgument('package', null, 'Optional package name to clear only one package');
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
            $packages = \Pinoox\Portal\App\AppEngine::all();
        }

        foreach ($packages as $pkg) {
            $pinkerDir = path('pinker', $pkg);
            \Pinoox\Portal\FileSystem::remove($pinkerDir);
            $output->writeln("<info>Pinker cache cleared for [{$pkg}]</info>");
        }

        return Command::SUCCESS;
    }
} 