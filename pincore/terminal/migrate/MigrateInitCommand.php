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

use pinoox\component\migration\Migrator;
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

    private $pincore = null;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->pincore = AppManager::getApp('pincore');

        $migrator = new Migrator($this->pincore['package'], 'init');

        try {
            $result = $migrator->run();
            $this->success($result);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        return Command::SUCCESS;
    }

}