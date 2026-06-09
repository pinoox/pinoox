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
    name: 'pinker:rebuild',
    description: 'Bake app.php and config files into the Pinker cache folder',
    aliases: ['bake'],
)]

class PinkerRebuildCommand extends Terminal
{
    use PinkerCommandSupport;

    protected function configure(): void
    {
        $this
            ->setHelp('Example: php pinoox pinker:rebuild com_my_shop')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package, platform, or all. Leave empty to pick from the list.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePinkerPackage($input, $output, $io);
        $rows = [];

        foreach ($this->pinkerEntries($package) as $entry) {
            $entry['pinker']->rebuild();
            $status = $entry['pinker']->status();
            $rows[] = [
                $entry['package'],
                $entry['label'],
                $status['env_sensitive'] ? 'runtime only' : 'rebuilt',
                $status['override_exists'] ? 'kept' : '-',
            ];
        }

        $io->title('Pinker Rebuild');
        $io->table(['Package', 'File', 'Cache', 'Override'], $rows);

        return Command::SUCCESS;
    }
}

