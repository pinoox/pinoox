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
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\Database\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


// the "name" and "description" arguments of AsCommand replace the
// static $defaultName and $defaultDescription properties
#[AsCommand(
    name: 'migrate',
    description: 'Migrate schemas.',
)]
class MigrateCommand extends Terminal
{
    protected function configure(): void
    {
        $this->addArgument('package', InputArgument::OPTIONAL, 'Enter the package name that you want to migrate schemas', $this->getDefaultPackage());
        $this->addOption('ignore-fk', 'f', InputOption::VALUE_NONE, 'Disable foreign key constraints');;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $ignoreFk = $input->getOption('ignore-fk');
        try {
            $package = $input->getArgument('package');

            if ($package === 'pincore') {
                $initializer = new Migrator('pincore', 'init');
                $initMessages = $initializer->init();
                $this->printMessages($initMessages);
            }

            if ($ignoreFk)
                Schema::disableForeignKeyConstraints();
            $migrator = new Migrator($package, 'run');
            $messages = $migrator->run();
            if ($ignoreFk)
                Schema::enableForeignKeyConstraints();

            $this->printMessages($messages);

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function printMessages($messages): void
    {
        if (empty($messages)) throw new Exception($messages);

        foreach ($messages as $message) {
            $this->success($message);
        }
    }

}