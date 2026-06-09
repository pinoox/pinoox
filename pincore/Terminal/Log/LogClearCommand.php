<?php

namespace Pinoox\Terminal\Log;

use Pinoox\Component\Log\LogConfig;
use Pinoox\Component\Terminal;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'log:clear',
    description: 'Clear the Pinoox log file',
    aliases: ['log:flush'],
)]

class LogClearCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this->addOption('package', 'p', InputOption::VALUE_OPTIONAL, 'App package or platform. Leave empty for active/default log.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $logPath = $this->resolveLogPath($input, $output);

        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
            $output->writeln("<info>Log file cleared:</info> {$logPath}");
            return Command::SUCCESS;
        } else {
            $output->writeln("<error>Log file not found:</error> {$logPath}");
            return Command::FAILURE;
        }
    }

    private function resolveLogPath(InputInterface $input, OutputInterface $output): string
    {
        $package = $this->readPackageInput($input, 'package', ['package']);

        if ($package === '' && $input->isInteractive() && $input->getOption('package') === null) {
            $io = new SymfonyStyle($input, $output);
            $answer = $io->confirm('Select a specific app log file?', false);

            if ($answer) {
                $package = $this->resolvePackageRequired($input, $output, $io, [
                    'sectionTitle' => 'Select log package',
                ]);
            }
        }

        if ($package === '') {
            return LogConfig::path();
        }

        return LogConfig::resolveForPackage($package)['path'];
    }
}

