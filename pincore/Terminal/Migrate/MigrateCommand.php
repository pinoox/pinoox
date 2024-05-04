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
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\DB;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        $this->addArgument('package', InputArgument::REQUIRED, 'Enter the package name that you want to migrate schemas');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        try {
            $package = $input->getArgument('package');

            if ($package === 'pincore') {
                $initializer = new Migrator('pincore', 'init');
                $initMessages = $initializer->init();
                $this->printMessages($initMessages);
            }

            $migrator = new Migrator($package, 'run');
            $messages = $migrator->run();
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
        if (!empty($messages)){
            foreach ($messages as $message) {
                $this->success($message);
            }
        }
    }

}