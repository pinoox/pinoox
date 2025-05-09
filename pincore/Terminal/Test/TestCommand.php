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

namespace Pinoox\Terminal\Test;

use Pinoox\Component\Terminal;
use Pinoox\Portal\Path;
use Pinoox\Portal\Config;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'test',
    description: 'Run Tests.',
)]
class TestCommand extends Terminal
{

    protected function configure(): void
    {
        $this->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Filter tests by name or annotation')
            ->addOption('unit', 'u', InputOption::VALUE_NONE, 'Run only unit tests')
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED, 'Run tests belonging to specific groups')
            ->addOption('coverage', 'c', InputOption::VALUE_NONE, 'Generate code coverage report')
            ->addOption('app', 'a', InputOption::VALUE_REQUIRED, 'Run tests for specific app');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        // Set environment to test mode
        Config::name('~pinoox')->set('mode', 'test');

        $this->info('Running tests...');

        $command = Path::get('~')  . '/vendor/bin/pest ';
 
        if ($input->getOption('filter'))
            $command .= ' --filter=' . $input->getOption('filter');

        if ($input->getOption('group'))
            $command .= ' --group=' . $input->getOption('group');

        if ($input->getOption('coverage'))
            $command .= ' --coverage';

        if ($input->getOption('unit'))
            $command .= ' --group=unit';

        if ($input->getOption('app')) {
            $appName = $input->getOption('app');
            $appPath = Path::get('~') . '/apps/' . $appName;
            
            if (!is_dir($appPath)) {
                $this->error("App '{$appName}' not found!");
            }
            
            $command .= ' ' . $appPath . '/tests';
        }

        // Execute the Pest tests
        passthru($command);

        return Command::SUCCESS;
    }

}