<?php

namespace Pinoox\Terminal\Schedule;

use Pinoox\Component\Terminal;
use Pinoox\Cron\ScheduleRegistry;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'schedule:list',
    description: 'List cron tasks registered by apps',
)]

class ScheduleListCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Shows scheduled tasks from boot.php and ScheduleRegistry.

Examples:
  php pinoox schedule:list
  php pinoox schedule:list com_my_shop
HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp(optional: true));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePackageFilter($input, $output, $io, [
            'sectionTitle' => 'Show schedules for',
        ]);
        $tasks = (new ScheduleRegistry())->all($package);

        if (empty($tasks)) {
            $io->success('No scheduled tasks found.');

            return Command::SUCCESS;
        }

        $rows = array_map(static function ($task) {
            $task = $task->toArray();

            return [
                $task['package'],
                $task['name'],
                $task['expression'],
                $task['type'],
                implode(', ', $task['flow']),
                $task['without_overlapping'] ? 'yes' : 'no',
                $task['description'] ?? '',
            ];
        }, $tasks);

        $io->title('Pinoox Schedule');
        $io->table(['Package', 'Name', 'Cron', 'Type', 'Flow', 'Lock', 'Description'], $rows);

        return Command::SUCCESS;
    }
}

