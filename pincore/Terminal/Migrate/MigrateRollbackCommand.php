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

use Pinoox\Component\Terminal;
use Pinoox\Portal\AppManager;
use Pinoox\Component\Migration\MigrationQuery;
use Pinoox\Portal\MigrationToolkit;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'migrate:rollback',
    description: 'Rollback the database migrations.',
)]
class MigrateRollbackCommand extends Terminal
{
    private string $package;

    private array $app;

    /**
     * @var MigrationToolkit
     */
    private $toolkit = null;

    protected function configure(): void
    {
        $this->addArgument('package', InputArgument::REQUIRED, 'Enter the package name of app you want to migrate schemas');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->package = $input->getArgument('package');

        $this->init();
        $this->reverse();

        return Command::SUCCESS;
    }

    private function init()
    {
        try {
            $this->app = AppManager::getApp($this->package);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->toolkit = MigrationToolkit::appPath($this->app['path'])
            ->migrationPath($this->app['migration'])
            ->package($this->app['package'])
            ->namespace($this->app['namespace'])
            ->action('rollback')
            ->load();

        if (!$this->toolkit->isSuccess()) {
            $this->error($this->toolkit->getErrors());
        }
    }

    private function reverse()
    {
        $migrations = $this->toolkit->getMigrations();

        if (empty($migrations)) {
            $this->success('Nothing to rollback.');
            $this->stop();
        }

        $batch = MigrationQuery::fetchLatestBatch($this->app['package']);

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