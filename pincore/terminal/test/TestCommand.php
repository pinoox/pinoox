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
            ->addOption('coverage', 'c', InputOption::VALUE_NONE, 'Generate code coverage report');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

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


        // Execute the Pest tests
        passthru($command);

        return Command::SUCCESS;
    }

}