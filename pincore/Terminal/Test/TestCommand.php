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
use Pinoox\Portal\Config;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'test',
    description: 'Run Pest/PHPUnit tests for an app or platform',
)]

class TestCommand extends Terminal
{
    use SelectsTestPackage;

    protected function configure(): void
    {
        $this
            ->setHelp('Examples: php pinoox test | php pinoox test com_my_shop --feature')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package or platform. Leave empty to pick from the list.')
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Run tests matching a name pattern')
            ->addOption('unit', 'u', InputOption::VALUE_NONE, 'Run only Unit tests')
            ->addOption('feature', null, InputOption::VALUE_NONE, 'Run only Feature tests')
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED, 'Run tests in a @group')
            ->addOption('coverage', 'c', InputOption::VALUE_NONE, 'Generate a code coverage report')
            ->addOption('app', 'a', InputOption::VALUE_REQUIRED, 'Same as package (alias)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $io = new SymfonyStyle($input, $output);

        // Set environment to test mode
        Config::name('~pinoox')->set('mode', 'test');

        try {
            $package = $this->resolvePackage($input, $output, $io);
            $testPath = $this->resolveTestPath($package, $input);

            if (!is_dir($testPath)) {
                $io->warning("No tests found for package '{$package}' at {$testPath}.");
                return Command::SUCCESS;
            }

            $io->title('Pinoox Tests');
            $io->definitionList(
                ['Package' => $package],
                ['Path' => $testPath]
            );

            $command = $this->buildPestCommand($testPath, $input);
            passthru($command, $exitCode);

            return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function resolveTestPath(string $package, InputInterface $input): string
    {
        if ($input->getOption('unit')) {
            $unitPath = $this->testPath($package, 'Unit');
            return is_dir($unitPath) ? $unitPath : $this->testPath($package);
        }

        if ($input->getOption('feature')) {
            $featurePath = $this->testPath($package, 'Feature');
            return is_dir($featurePath) ? $featurePath : $this->testPath($package);
        }

        return $this->testPath($package);
    }

    private function buildPestCommand(string $testPath, InputInterface $input): string
    {
        $command = escapeshellarg(path('~/vendor/bin/pest')) . ' ' . escapeshellarg($testPath) . ' --colors=always';

        if ($input->getOption('filter')) {
            $command .= ' --filter=' . escapeshellarg((string)$input->getOption('filter'));
        }

        if ($input->getOption('group')) {
            $command .= ' --group=' . escapeshellarg((string)$input->getOption('group'));
        }

        if ($input->getOption('coverage')) {
            $command .= ' --coverage';
        }

        return $command;
    }

}

