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

use Pinoox\Component\Helpers\PhpFile\TestFile;
use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'test:create',
    description: 'Create a Pest test file in an app or platform',
)]

class TestCreateCommand extends Terminal
{
    use SelectsTestPackage;

    protected function configure(): void
    {
        $this
            ->setHelp('Example: php pinoox test:create ProductTest com_my_shop --feature')
            ->addArgument('TestName', InputArgument::REQUIRED, 'Test class name (e.g. ProductTest)')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package or platform. Leave empty to pick from the list.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite an existing test file')
            ->addOption('unit', null, InputOption::VALUE_NONE, 'Create under tests/Unit instead of tests/Feature');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $io = new SymfonyStyle($input, $output);

        $testName = $input->getArgument('TestName');
        $package = $this->resolvePackage($input, $output, $io);
        $subFolder = $input->getOption('unit') ? 'Unit' : 'Feature';

        if ($package !== 'platform' && $package !== 'all') {
            $appDir = \Pinoox\Portal\App\AppEngine::path($package);
            $bootstrapFile = $appDir . '/tests/bootstrap.php';
            if (!is_file($bootstrapFile)) {
                TestFile::scaffoldAppTests($package, $appDir);
                $io->note("Created app test kit (bootstrap.php, README.md) for {$package}.");
            }
        }

        $exportPath = $this->testPath($package, $subFolder) . '/' . $testName . '.php';

        if (file_exists($exportPath) && !$input->getOption('force')) {
            $this->error($testName . "Test file already exists");
            return Command::FAILURE;
        }

        if (!is_dir(dirname($exportPath))) {
            mkdir(dirname($exportPath), 0777, true);
        }

        if (TestFile::create($exportPath, $testName, $package, (bool) $input->getOption('unit'))) {
            $this->success("Test file created successfully: $exportPath");
            return Command::SUCCESS;
        } else {
            $this->error("Failed to create the test file: $exportPath");
            return Command::FAILURE;
        }

    }
}

