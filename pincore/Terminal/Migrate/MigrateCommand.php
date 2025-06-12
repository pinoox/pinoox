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

use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Migration\Migrator;
use Pinoox\Component\Terminal; 
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pinoox\Component\Migration\MigrationQuery;


// the "name" and "description" arguments of AsCommand replace the
// static $defaultName and $defaultDescription properties
#[AsCommand(
    name: 'migrate',
    description: 'Migrate schemas.',
)]
class MigrateCommand extends Terminal
{
    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this->addArgument('package', InputArgument::OPTIONAL, 'Enter the package name that you want to migrate schemas', $this->getDefaultPackage())
            ->addOption('ignore-fk', 'f', InputOption::VALUE_NONE, 'Disable foreign key constraints')
            ->addOption('dbconfig', null, InputOption::VALUE_NONE, 'Show current database configuration')
            ->addOption('status', 's', InputOption::VALUE_NONE, 'Show migration status')
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Reset all migrations');
    }

    /**
     * Execute the command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $ignoreFk = $input->getOption('ignore-fk');
        $showStatus = $input->getOption('status');
        $reset = $input->getOption('reset');

        if ($input->getOption('dbconfig')) {
            $config = \Pinoox\Portal\Database\DB::connection()->getConfig();
            $output->writeln(json_encode($config, JSON_PRETTY_PRINT));
            return 0;
        }

        if ($showStatus) {
            return $this->showStatus($input->getArgument('package'), $output);
        }

        try {
            $package = $input->getArgument('package');

            if ($reset) {
                $migrator = new Migrator($package);
                $result = $migrator->reset();
                foreach ($result as $message) {
                    $output->writeln($message);
                }
                return Command::SUCCESS;
            }

            if ($ignoreFk) {
                \Pinoox\Portal\Database\DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            $migrator = new Migrator($package);
            $result = $migrator->run();
            foreach ($result as $message) {
                $output->writeln($message);
            }

            if ($ignoreFk) {
                \Pinoox\Portal\Database\DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>" . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }

    /**
     * @throws Exception
     */
    private function printMessages($messages): void
    {
        if (!empty($messages)){
            foreach ($messages as $message) {
                $this->success($message);
            }
        }
    }

    /**
     * Show migration status
     */
    private function showStatus(string $app, OutputInterface $output): int
    {
        try {
            $records = MigrationQuery::fetchAllByBatch(null, $app);
            
            if (empty($records)) {
                $output->writeln("No migrations found for app: " . $app);
                return Command::SUCCESS;
            }

            $output->writeln("\nMigration Status for app: " . $app);
            $output->writeln(str_repeat('-', 80));
            $output->writeln(sprintf("%-50s %-10s %-20s", "Migration", "Batch", "Status"));
            $output->writeln(str_repeat('-', 80));

            foreach ($records as $record) {
                $output->writeln(sprintf("%-50s %-10s %-20s",
                    $record['migration'],
                    $record['batch'],
                    'Completed'
                ));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>" . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }
}