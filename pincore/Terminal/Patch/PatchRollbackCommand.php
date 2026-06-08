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
    name: 'patch:rollback',
    description: 'Rollback the last executed patch',
)]

class PatchRollbackCommand extends Terminal
{
    use SelectsMigrationPackage;

    protected function configure(): void
    {
        $this
            ->setHelp('Example: php pinoox patch:rollback com_my_shop')
            ->addArgument('patch', InputArgument::REQUIRED, 'Patch name or class to rollback')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package or platform. Leave empty to pick from the list.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $package = $this->resolvePackage($input, $output, new SymfonyStyle($input, $output));
        $target = (string)$input->getArgument('patch');

        try {
            (new Migrator('platform'))->run();

            $toolkit = new PatchToolkit();
            $toolkit->package($package)->load();

            if (!$toolkit->isSuccess()) {
                $this->error($toolkit->getErrors());

                return Command::FAILURE;
            }

            foreach ($toolkit->getPatches() as $patch) {
                if (!$this->matches($patch, $target)) {
                    continue;
                }

                if (!$patch['ran']) {
                    $this->warning('Patch has not been executed: ' . $patch['name']);

                    return Command::SUCCESS;
                }

                if (!$patch['can_rollback']) {
                    $this->warning('Patch does not declare rollback support: ' . $patch['name']);

                    return Command::SUCCESS;
                }

                $startedAt = microtime(true);
                $patch['instance']->down();
                $toolkit->deleteSuccessRecord($patch['name']);
                $toolkit->recordRolledBack($patch['name'], $patch['checksum'], $this->durationMs($startedAt), [
                    'class' => $patch['class'],
                    'description' => $patch['description'],
                ]);

                $this->success('Rolled back: ' . $patch['name']);

                return Command::SUCCESS;
            }

            $this->error('Patch not found: ' . $target);

            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function matches(array $patch, string $target): bool
    {
        $normalizedTarget = $this->normalizePatchName($target);
        $normalizedPatch = $this->normalizePatchName($patch['name']);

        return $patch['name'] === $target
            || $patch['class'] === $target
            || basename(str_replace('\\', '/', $patch['class'])) === $target
            || $normalizedPatch === $normalizedTarget;
    }

    private function normalizePatchName(string $patch): string
    {
        $patch = pathinfo($patch, PATHINFO_FILENAME);

        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $patch, $matches)) {
            return $matches[1];
        }

        return $patch;
    }

    private function durationMs(float $startedAt): int
    {
        return (int)round((microtime(true) - $startedAt) * 1000);
    }
}

