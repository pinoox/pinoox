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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'patch:run',
    description: 'Run pending data patches for an app',
    aliases: ['patch'],
)]

class PatchRunCommand extends Terminal
{
    use SelectsMigrationPackage;

    protected function configure(): void
    {
        $this
            ->setHelp('Example: php pinoox patch:run com_my_shop')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package or platform. Leave empty to pick from the list.')
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Run a specific patch class')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Continue when a patch fails');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $package = $this->resolvePackage($input, $output, new SymfonyStyle($input, $output));
        $class = $input->getOption('class');

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

                if (!$patch['should_run']) {
                    $this->info('Skipped by condition: ' . $patch['name']);
                    $toolkit->recordSkipped($patch['name'], $patch['checksum'], null, $this->metadata($patch));
                    $skippedCount++;
                    continue;
                }

                try {
                    $this->info('Running: ' . $patch['name']);
                    $startedAt = microtime(true);
                    $patch['instance']->run();
                    $toolkit->recordSuccess($patch['name'], $patch['checksum'], $this->durationMs($startedAt), $this->metadata($patch));
                    $this->success('Completed: ' . $patch['name']);
                    $successCount++;
                } catch (\Throwable $e) {
                    $toolkit->recordFailed($patch['name'], $e, $patch['checksum'] ?? null, isset($startedAt) ? $this->durationMs($startedAt) : null, $this->metadata($patch));
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

    private function durationMs(float $startedAt): int
    {
        return (int)round((microtime(true) - $startedAt) * 1000);
    }

    private function metadata(array $patch): array
    {
        $metadata = [];
        if (isset($patch['instance'])) {
            try {
                $metadata = $patch['instance']->metadata();
            } catch (\Throwable $e) {
                $metadata = ['metadata_error' => $e->getMessage()];
            }
        }

        return array_filter([
            'class' => $patch['class'] ?? null,
            'description' => $patch['description'] ?? null,
            'metadata' => $metadata,
        ]);
    }
}

