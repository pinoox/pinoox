<?php

namespace Pinoox\Terminal\Patch;

use Pinoox\Component\Database\Patch\PatchToolkit;
use Pinoox\Component\Migration\Migrator;
use Pinoox\Component\Terminal;
use Pinoox\Terminal\Migrate\SelectsMigrationPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'patch:status',
    description: 'Show which patches ran and which are pending',
)]

class PatchStatusCommand extends Terminal
{
    use SelectsMigrationPackage;

    protected function configure(): void
    {
        $this
            ->setHelp('Example: php pinoox patch:status com_my_shop')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package or platform. Leave empty to pick from the list.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $package = $this->resolvePackage($input, $output, new SymfonyStyle($input, $output));

        try {
            (new Migrator('platform'))->run();

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
                $record = $patch['record'] ?? [];
                $checksum = $patch['checksum'] ? substr($patch['checksum'], 0, 12) : '-';

                $rows[] = [
                    $package,
                    $patch['name'],
                    $patch['status'],
                    $patch['should_run'] ? 'yes' : 'no',
                    $checksum,
                    $record['duration_ms'] ?? '-',
                    $record['executed_at'] ?? '-',
                    $patch['created_at'],
                    $patch['description'] ?: '-',
                ];
            }

            $this->table(['App', 'Patch', 'Status', 'Should run', 'Checksum', 'Duration ms', 'Executed at', 'Created at', 'Description'], $rows);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}

