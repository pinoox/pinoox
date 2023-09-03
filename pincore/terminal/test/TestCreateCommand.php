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

namespace pinoox\terminal\test;

use pinoox\component\helpers\PhpFile\TestFile;
use pinoox\component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'test:create',
    description: 'Create Test',
)]
class TestCreateCommand extends Terminal
{
    protected function configure(): void
    {
        $this->addArgument('TestName', InputArgument::REQUIRED, 'Name of the test class')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing test case file')
            ->addOption('unit', null, InputOption::VALUE_NONE, 'Create a unit test in the Unit path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $testName = $input->getArgument('TestName');
        $subFolder = $input->getOption('unit') ? 'Unit' : 'Feature';

        $exportPath = 'tests' . DS . $subFolder . DS . $testName . '.php';

        if (file_exists($exportPath)) {
            $this->error($testName . "Test file already exists");
            return Command::FAILURE;
        }

        if (TestFile::create($exportPath, $testName, 'something')) {
            $this->success("Test file created successfully: $exportPath");
            return Command::SUCCESS;
        } else {
            $this->error("Failed to create the test file: $exportPath");
            return Command::FAILURE;
        }

    }
}