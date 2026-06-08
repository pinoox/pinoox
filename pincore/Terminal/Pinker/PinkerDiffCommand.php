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
    name: 'pinker:diff',
    description: 'Show differences between Pinker cache and source files',
)]

class PinkerDiffCommand extends Terminal
{
    use PinkerCommandSupport;

    protected function configure(): void
    {
        $this
            ->setHelp('Example: php pinoox pinker:diff com_my_shop')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package, platform, or all. Leave empty to pick from the list.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePinkerPackage($input, $output, $io, false);
        $io->title('Pinker Diff');

        foreach ($this->pinkerEntries($package) as $entry) {
            $status = $entry['pinker']->status();

            if (!$status['override_exists'] || !is_file($status['override'])) {
                continue;
            }

            $data = include $status['override'];
            $sets = $data['data'] ?? [];
            $removes = $data['remove'] ?? [];

            $io->section($entry['package'] . ' / ' . $entry['label']);
            $io->table(['Type', 'Path', 'Value'], [
                ...array_map(static fn($path, $value) => ['set', $path, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)], array_keys($sets), $sets),
                ...array_map(static fn($path) => ['remove', $path, '-'], $removes),
            ]);
        }

        return Command::SUCCESS;
    }
}

