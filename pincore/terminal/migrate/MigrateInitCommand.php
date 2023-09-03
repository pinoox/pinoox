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

namespace pinoox\terminal\migrate;

use pinoox\component\Terminal;
use pinoox\portal\AppManager;
use pinoox\portal\MigrationToolkit;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'migrate:init',
    description: 'Initialize migration repository and create tables.',
)]
class MigrateInitCommand extends Terminal
{

    /**
     * @var MigrationToolkit
     */
    private $toolkit = null;

    private $pincore = null;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->init();
        $this->migrate();

        return Command::SUCCESS;
    }

    private function init()
    {
        $this->pincore = AppManager::getApp('pincore');
        $this->toolkit = MigrationToolkit::appPath($this->pincore['path'])
            ->migrationPath($this->pincore['migration'])
            ->package($this->pincore['package'])
            ->namespace($this->pincore['namespace'])
            ->action('init')
            ->load();
        
        if (!$this->toolkit->isSuccess()) {
            $this->error($this->toolkit->getErrors());
        }
    }

    private function migrate()
    {
        $migrations = $this->toolkit->getMigrations();

        if (empty($migrations)) {
            $this->success('Nothing to migrate.');
        }

        foreach ($migrations as $m) {
            $start_time = microtime(true);
            $this->success('Migrating: ');
            $this->success($m['fileName']);
            $this->newline();
            $obj = new $m['classObject']();
            $obj->up();

            $end_time = microtime(true);
            $exec_time = $end_time - $start_time;

            //end migrating
            $this->success('Migrated: ' . $m['fileName']);
            $this->info(' (' . substr($exec_time, 0, 5) . 'ms)');
        }
    }
}