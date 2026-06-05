<?php

namespace Pinoox\Terminal\Patch;

use Pinoox\Component\Database\Patch\PatchToolkit;
use Pinoox\Component\Migration\Migrator;
use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'patch:status',
    description: 'Show app patch status.',
)]
class PatchStatusCommand extends Terminal
{
    protected function configure(): void
    {
        $this->addArgument('package', InputArgument::OPTIONAL, 'The package to inspect', $this->getDefaultPackage());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $package = (string)$input->getArgument('package');

        try {
            (new Migrator('pincore'))->run();

            $toolkit = new PatchToolkit();
            $toolkit->package($package)->load();

            if (!$toolkit->isSuccess()) {
                $this->error($toolkit->getErrors());

                return Command::FAILURE;
            }

            $patches = $toolkit->getPatches();

            if (empty($patches)) {
                $this->success('There are no patches!');

                return Command::SUCCESS;
            }

            $rows = [];
            foreach ($patches as $patch) {
                $rows[] = [
                    $package,
                    $patch['name'],
                    $patch['ran'] ? 'ran' : 'pending',
                    $patch['created_at'],
                ];
            }

            $this->table(['App', 'Patch', 'Status', 'Created at'], $rows);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
