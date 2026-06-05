<?php

namespace Pinoox\Terminal\Patch;

use Pinoox\Component\Database\Patch\PatchToolkit;
use Pinoox\Component\Migration\Migrator;
use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'patch:run',
    description: 'Run pending app patches.',
)]
class PatchRunCommand extends Terminal
{
    protected function configure(): void
    {
        $this->addArgument('package', InputArgument::OPTIONAL, 'The package to patch', $this->getDefaultPackage())
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Run a specific patch class')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Continue when a patch fails');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $package = (string)$input->getArgument('package');
        $class = $input->getOption('class');

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
                $this->warning('No patches found in package: ' . $package);

                return Command::SUCCESS;
            }

            $successCount = 0;
            $skippedCount = 0;
            $failCount = 0;

            foreach ($patches as $patch) {
                if ($class && $patch['class'] !== $class && basename(str_replace('\\', '/', $patch['class'])) !== $class) {
                    continue;
                }

                if ($patch['ran']) {
                    $this->info('Skipped: ' . $patch['name']);
                    $skippedCount++;
                    continue;
                }

                try {
                    $this->info('Running: ' . $patch['name']);
                    $patch['instance']->run();
                    $toolkit->record($patch['name']);
                    $this->success('Completed: ' . $patch['name']);
                    $successCount++;
                } catch (\Throwable $e) {
                    $this->error('Failed: ' . $patch['name'] . ' - ' . $e->getMessage());
                    $failCount++;

                    if (!$input->getOption('force')) {
                        return Command::FAILURE;
                    }
                }
            }

            $this->info('Patch summary: ' . $successCount . ' executed, ' . $skippedCount . ' skipped, ' . $failCount . ' failed');

            return $failCount === 0 ? Command::SUCCESS : Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
