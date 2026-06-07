<?php

namespace Pinoox\Terminal\Schedule;

use Pinoox\Component\Terminal;
use Pinoox\Cron\ScheduleRunner;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'schedule:run',
    description: 'Run due cron tasks (for system crontab)',
)]

class ScheduleRunCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Executes scheduled tasks whose cron expression is due now.

Examples:
  php pinoox schedule:run
  php pinoox schedule:run com_my_shop
  php pinoox schedule:run --all
  php pinoox schedule:run --dry-run

Typical crontab entry:
  * * * * * php /path/to/pinoox schedule:run >> /dev/null 2>&1
HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp(optional: true))
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Run only one task by name')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Run all matching tasks even when they are not due')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List matching tasks without executing them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePackageFilter($input, $output, $io, [
            'sectionTitle' => 'Run schedules for',
        ]);
        $results = (new ScheduleRunner())->run(
            package: $package,
            name: $input->getOption('name') ?: null,
            all: (bool) $input->getOption('all'),
            dryRun: (bool) $input->getOption('dry-run'),
        );

        if (empty($results)) {
            $io->success('No scheduled tasks are due.');

            return Command::SUCCESS;
        }

        $rows = [];
        $failed = false;
        foreach ($results as $result) {
            $task = $result->task->toArray();
            $failed = $failed || $result->status === 'failed';
            $rows[] = [
                $task['package'],
                $task['name'],
                $task['expression'],
                $result->status,
                trim($result->output),
            ];
        }

        $io->table(['Package', 'Name', 'Cron', 'Status', 'Output'], $rows);

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}

