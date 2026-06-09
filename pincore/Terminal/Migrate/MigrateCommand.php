<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Terminal\Migrate;

use Pinoox\Component\Migration\Migrator;
use Pinoox\Component\Terminal;
use Pinoox\Portal\Database\DB;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// the "name" and "description" arguments of AsCommand replace the
// static $defaultName and $defaultDescription properties
#[AsCommand(
    name: 'migrate',
    description: 'Run pending database migrations for an app or platform',
    aliases: ['mg'],
)]

class MigrateCommand extends Terminal
{
    use SelectsMigrationPackage;

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Runs new migration files from database/migrations/.

Examples:
  php pinoox migrate
  php pinoox migrate com_my_shop
  php pinoox migrate --status
  php pinoox migrate --reset
HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, 'App package or platform. Leave empty to pick from the list.')
            ->addOption('ignore-fk', 'f', InputOption::VALUE_NONE, 'Disable foreign key checks during migration')
            ->addOption('dbconfig', null, InputOption::VALUE_NONE, 'Print the active database connection settings')
            ->addOption('status', 's', InputOption::VALUE_NONE, 'Show which migrations ran and which are pending')
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Rollback all migrations, then run them again')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Run even when tables already exist');
    }

    /**
     * Execute the command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $io = new SymfonyStyle($input, $output);
        $ignoreFk = $input->getOption('ignore-fk');
        $showStatus = $input->getOption('status');
        $reset = $input->getOption('reset');
        $force = $input->getOption('force');

        if ($input->getOption('dbconfig')) {
            $config = DB::connection()->getConfig();
            unset($config['password']);
            $io->title('Pinoox Database Configuration');
            $io->writeln(json_encode($config, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $package = $this->resolvePackage($input, $output, $io);
        $this->printHeader($io, $package);

        if ($showStatus) {
            return $this->showStatus($package, $io);
        }

        try {
            if ($reset) {
                $migrator = new Migrator($package);
                $result = $migrator->reset();
                $this->printMessages($io, $result);
                return Command::SUCCESS;
            }

            if ($ignoreFk) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                $io->note('Foreign key checks are disabled for this migration run.');
            }

            $migrator = new Migrator($package, 'run', ['force' => $force]);
            $result = $migrator->run();
            $this->printResult($io, $result);

            if ($ignoreFk) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function printHeader(SymfonyStyle $io, string $package): void
    {
        $io->title('Pinoox Migration');

        $connectionName = DB::connectionNameForPackage($package);
        $connection = DB::getConnection($connectionName);
        $config = $connection->getConfig();

        $io->definitionList(
            ['Package' => $package],
            ['Connection' => $this->displayConnectionName($package, $connectionName)],
            ['Database' => $config['database'] ?? '-'],
            ['Table prefix' => $connection->getTablePrefix() ?: '(none)']
        );
    }

    private function displayConnectionName(string $package, string $connectionName): string
    {
        if ($package === 'platform') {
            return 'default';
        }

        $prefix = 'app_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $package) . '_';

        if (str_starts_with($connectionName, $prefix)) {
            return substr($connectionName, strlen($prefix)) ?: 'default';
        }

        return $connectionName === 'platform' ? 'default' : $connectionName;
    }

    private function printResult(SymfonyStyle $io, array $result): void
    {
        if (isset($result['executed']) || isset($result['skipped'])) {
            $executed = $result['executed'] ?? [];
            $skipped = $result['skipped'] ?? [];

            if (!empty($executed)) {
                $io->section('Executed');
                $io->listing($executed);
            }

            if (!empty($skipped)) {
                $io->section('Skipped');
                $io->listing($skipped);
            }

            if (empty($executed) && empty($skipped)) {
                $io->success('Nothing to migrate.');
                return;
            }

            $io->success(sprintf(
                'Migration completed. Executed: %d, skipped: %d, total: %d.',
                count($executed),
                count($skipped),
                $result['total'] ?? count($executed) + count($skipped)
            ));

            return;
        }

        $this->printMessages($io, $result);
    }

    private function printMessages(SymfonyStyle $io, array $messages): void
    {
        foreach ($messages as $message) {
            if ($message === 'Nothing to migrate.') {
                $io->success($message);
                continue;
            }

            $io->writeln((string)$message);
        }
    }

    /**
     * Show migration status
     */
    private function showStatus(string $app, SymfonyStyle $io): int
    {
        try {
            $records = (new Migrator($app))->status();

            if (empty($records)) {
                $io->success("There are no migrations for package: " . $app);
                return Command::SUCCESS;
            }

            $rows = array_map(static fn($record) => [
                $record['migration'],
                $record['batch'] ?? '-',
                $record['status'] === 'migrated' ? 'Done' : 'Pending',
                $record['created_at'] ?? '-',
            ], $records);

            $io->table(['Migration', 'Batch', 'Status', 'Created at'], $rows);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}

