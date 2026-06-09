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

use Pinoox\Component\Migration\MigrationQuery;
use Pinoox\Component\Migration\MigrationToolkit;
use Pinoox\Component\Terminal;
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\Database\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'migrate:rollback',
    description: 'Rollback the last batch of migrations',
    aliases: ['mg:rollback', 'mg:back'],
)]

class MigrateRollbackCommand extends Terminal
{
    use SelectsMigrationPackage;

    private string $package;

    private $mig = null;

    protected function configure(): void
    {
        $this
            ->setHelp('Example: php pinoox migrate:rollback com_my_shop')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package or platform. Leave empty to pick from the list.')
            ->addOption('ignore-fk', 'f', InputOption::VALUE_NONE, 'Disable foreign key checks during rollback');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $ignoreFk = $input->getOption('ignore-fk');

        $this->package = $this->resolvePackage($input, $output, new SymfonyStyle($input, $output));

        $this->init();

        if ($ignoreFk)
            Schema::disableForeignKeyConstraints();

        $this->reverse();

        if ($ignoreFk)
            Schema::enableForeignKeyConstraints();

        return Command::SUCCESS;
    }

    private function init()
    {
        $this->mig = new MigrationToolkit();
        $this->mig->package($this->package)
            ->action('rollback')
            ->load();

        if (!$this->mig->isSuccess()) {
            $this->error($this->mig->getErrors());
        }
    }

    private function reverse()
    {
        $migrations = $this->mig->getMigrations();

        if (empty($migrations)) {
            $this->success('Nothing to rollback.');
            $this->stop();
        }

        $batch = MigrationQuery::fetchLatestBatch($this->package);

        foreach ($migrations as $m) {

            $start_time = microtime(true);
            $this->warning('Rolling back: ');
            $this->info($m['fileName']);
            $this->newLine();

            $class = require_once $m['migrationFile'];
            $class->down();

            MigrationQuery::delete($batch, $m['packageName']);

            $end_time = microtime(true);
            $exec_time = $end_time - $start_time;

            //end migrating
            $this->success('Rolled back: ');
            $this->info($m['fileName'] . ' (' . substr($exec_time, 0, 5) . 'ms)');
            $this->newLine();
        }
    }

}

