<?php

namespace Pinoox\Terminal\Pincore;

use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\FileSystem;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'reset',
    description: 'Remove Pinker baked files and restore app config from source',
)]

class ResetCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Deletes Pinker cache folders so config is rebuilt from source on next request.

Examples:

  php pinoox reset

  php pinoox reset com_my_shop

HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePackageRequired($input, $output, $io, [
            'sectionTitle' => 'Reset Pinker for',
        ]);

        foreach ($this->pinkerDirs($package) as $pinkerDir) {
            FileSystem::remove($pinkerDir);
        }

        $io->success('Pinker directory [' . $package . '] removed successfully.');

        return Command::SUCCESS;
    }

    private function pinkerDirs(string $package): array
    {
        if ($package === 'platform') {
            return [
                path('~/pinker/pincore'),
                path('~/pinker/config'),
                path('~/pinker/system'),
            ];
        }

        return [path('~/pinker/apps/' . $package)];
    }
}

