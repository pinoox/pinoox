<?php

namespace Pinoox\Terminal\Log;

use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'log:clear',
    description: 'Clear the Pinoox log file'
)]
class LogClearCommand extends Terminal
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        // Get log file path from config
        $config = \Pinoox\Portal\Config::file('pinoox')->get('log');
        $logPath = $config['path'] ?? (sys_get_temp_dir() . '/pinoox.log');

        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
            $output->writeln("<info>Log file cleared:</info> {$logPath}");
            return Command::SUCCESS;
        } else {
            $output->writeln("<error>Log file not found:</error> {$logPath}");
            return Command::FAILURE;
        }
    }
} 