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
    name: 'pinker:clear',
    description: 'Remove baked Pinker files (keeps runtime overrides by default)',
)]

class PinkerClearCommand extends Terminal
{
    use PinkerCommandSupport;

    protected function configure(): void
    {
        $this
            ->setHelp('Example: php pinoox pinker:clear com_my_shop --state')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package, platform, or all. Leave empty to pick from the list.')
            ->addOption('state', null, InputOption::VALUE_NONE, 'Also delete runtime override files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePinkerPackage($input, $output, $io);
        $clearState = (bool)$input->getOption('state');
        $count = 0;

        foreach ($this->pinkerEntries($package) as $entry) {
            $entry['pinker']->removeCache();

            if ($clearState) {
                $entry['pinker']->removeOverride();
            }

            $count++;
        }

        $io->success($clearState
            ? "Cleared {$count} Pinker cache files and their overrides."
            : "Cleared {$count} Pinker cache files. Runtime overrides were preserved.");

        return Command::SUCCESS;
    }
}

