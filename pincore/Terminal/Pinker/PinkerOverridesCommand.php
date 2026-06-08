<?php

namespace Pinoox\Terminal\Pinker;

use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pinker:overrides',
    description: 'List or clear Pinker runtime override files',
)]

class PinkerOverridesCommand extends Terminal
{
    use PinkerCommandSupport;

    protected function configure(): void
    {
        $this
            ->setHelp('Example: php pinoox pinker:overrides com_my_shop --clear')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package, platform, or all. Leave empty to pick from the list.')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Delete matching override files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePinkerPackage($input, $output, $io);
        $clear = (bool)$input->getOption('clear');
        $rows = [];

        foreach ($this->pinkerEntries($package) as $entry) {
            $status = $entry['pinker']->status();

            if (!$status['override_exists']) {
                continue;
            }

            if ($clear) {
                $entry['pinker']->removeOverride();
            }

            $rows[] = [
                $entry['package'],
                $entry['label'],
                $status['override_sets'],
                $status['override_removes'],
                $this->formatTime($status['override_updated_at']),
                $this->relativePath($status['override']),
            ];
        }

        $io->title($clear ? 'Pinker Overrides Cleared' : 'Pinker Overrides');

        if ($rows === []) {
            $io->success('No runtime overrides were found.');
            return Command::SUCCESS;
        }

        $io->table(['Package', 'File', 'Sets', 'Removes', 'Updated', 'State file'], $rows);

        return Command::SUCCESS;
    }
}

