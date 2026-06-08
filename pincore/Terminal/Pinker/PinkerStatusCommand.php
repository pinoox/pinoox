<?php

namespace Pinoox\Terminal\Pinker;

use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pinker:status',
    description: 'Compare Pinker cache with source and override files',
)]

class PinkerStatusCommand extends Terminal
{
    use PinkerCommandSupport;

    protected function configure(): void
    {
        $this
            ->setHelp('Example: php pinoox pinker:status com_my_shop')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package, platform, or all. Leave empty to pick from the list.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePinkerPackage($input, $output, $io);
        $entries = $this->pinkerEntries($package);

        $io->title('Pinker Status');

        if ($entries === []) {
            $io->warning('No Pinker-managed files were found.');
            return Command::SUCCESS;
        }

        $rows = [];

        foreach ($entries as $entry) {
            $status = $entry['pinker']->status();
            $state = match (true) {
                $status['env_sensitive'] => 'runtime',
                !$status['cache_exists'] => 'missing',
                $status['cache_valid'] => 'fresh',
                default => 'stale',
            };

            $rows[] = [
                $entry['package'],
                $entry['label'],
                $state,
                $status['cache_exists'] ? 'yes' : 'no',
                $status['override_exists'] ? 'yes' : 'no',
                $this->formatTime($status['source_mtime']),
            ];
        }

        $io->table(['Package', 'File', 'State', 'Cache', 'Override', 'Source modified'], $rows);
        $io->note('runtime = source uses env(); defined .env keys override pinker/state; unset keys fall back to pinker.');

        return Command::SUCCESS;
    }
}

