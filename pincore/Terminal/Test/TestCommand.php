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
    aliases: ['pest'],
)]

class TestCommand extends Terminal
{
    use SelectsTestPackage;

    /** @var list<string> */
    private const PLATFORM_SUITES = [
        'Pinoox',
        'Server',
        'Routing',
        'Http',
        'App',
        'Portal',
        'Installer',
        'Theme',
        'Config',
        'Database',
        'Integration',
        'Unit',
    ];

    protected function configure(): void
    {
        $this
            ->setHelp(<<<'HELP'
Run platform or app tests with Pest.

Examples:
  php pinoox test
  php pinoox test all
  php pinoox test --all
  php pinoox test platform --suite=Database
  php pinoox test com_my_shop --feature
  php pinoox test all --exclude-group=non-isolated
  php pinoox test --list-suites

Isolation:
  Most tests use com_test_* apps and tests/Fixtures/sandbox (auto-cleaned).
  Tests tagged @group non-isolated may read local pinker/state or need MySQL/Redis.
  Use --exclude-group=non-isolated for a fast isolated CI run.
HELP)
            ->addArgument('package', InputArgument::OPTIONAL, 'App package, platform, or all. Leave empty to pick from the list.')
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Run tests matching a name pattern')
            ->addOption('unit', 'u', InputOption::VALUE_NONE, 'Run only Unit tests')
            ->addOption('feature', null, InputOption::VALUE_NONE, 'Run only Feature tests')
            ->addOption('suite', 's', InputOption::VALUE_REQUIRED, 'Run a phpunit.xml testsuite (platform only)')
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED, 'Run tests in a @group')
            ->addOption('exclude-group', null, InputOption::VALUE_REQUIRED, 'Skip tests in a @group (e.g. non-isolated)')
            ->addOption('coverage', 'c', InputOption::VALUE_NONE, 'Generate a code coverage report')
            ->addOption('app', 'a', InputOption::VALUE_REQUIRED, 'Same as package (alias)')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Run platform tests and every app that has a tests/ folder')
            ->addOption('list-suites', null, InputOption::VALUE_NONE, 'List platform test suites and exit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $io = new SymfonyStyle($input, $output);

        Config::name('~pinoox')->set('mode', 'test');

        if ($input->getOption('list-suites')) {
            $io->title('Platform test suites');
            $io->listing(self::PLATFORM_SUITES);
            $io->note('Run: php pinoox test platform --suite=Server');

            return Command::SUCCESS;
        }

        try {
            $package = $this->resolvePackage($input, $output, $io);
            $suite = (string) ($input->getOption('suite') ?? '');

            if ($suite !== '' && $package !== 'platform' && $package !== 'all') {
                $io->warning('The --suite option applies to platform tests only. Running app test path instead.');
                $suite = '';
            }

            if ($suite !== '' && !in_array($suite, self::PLATFORM_SUITES, true)) {
                throw new \InvalidArgumentException(sprintf(
                    "Unknown testsuite '%s'. Run php pinoox test --list-suites.",
                    $suite,
                ));
            }

            if ($package === 'all') {
                $testPaths = $this->allTestPaths($input, $suite);

                if ($testPaths === [] && $suite === '') {
                    $io->warning('No test directories found (platform or apps).');

                    return Command::SUCCESS;
                }

                $io->title('Pinoox Tests');
                $io->definitionList(
                    ['Package' => 'all'],
                    ['Target' => $suite !== '' ? "testsuite:{$suite} + app tests" : implode(', ', $testPaths)],
                );

                $command = $this->buildPestCommand($testPaths, $input, $suite);
                passthru($command, $exitCode);

                return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
            }

            $testPath = $this->resolveTestPath($package, $input, $suite);

            if ($suite === '' && !is_dir($testPath)) {
                $io->warning("No tests found for package '{$package}' at {$testPath}.");

                return Command::SUCCESS;
            }

            $io->title('Pinoox Tests');
            $io->definitionList(
                ['Package' => $package],
                ['Target' => $suite !== '' ? "testsuite:{$suite}" : $testPath],
            );

            $command = $this->buildPestCommand([$testPath], $input, $suite);
            passthru($command, $exitCode);

            return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function resolveTestPath(string $package, InputInterface $input, string $suite): string
    {
        if ($suite !== '') {
            return $this->testPath('platform');
        }

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

    /**
     * @param list<string> $testPaths
     */
    private function buildPestCommand(array $testPaths, InputInterface $input, string $suite): string
    {
        $parts = [
            escapeshellarg(PHP_BINARY),
            escapeshellarg(path('~/vendor/bin/pest')),
            '--configuration=' . escapeshellarg(path('~/phpunit.xml')),
            '--colors=always',
        ];

        $platformRoot = $this->testPath('platform');

        if ($suite !== '') {
            $parts[] = '--testsuite=' . escapeshellarg($suite);
        }

        foreach ($testPaths as $testPath) {
            if ($suite !== '' && $testPath === $platformRoot) {
                continue;
            }

            $parts[] = escapeshellarg($testPath);
        }

        if ($input->getOption('filter')) {
            $parts[] = '--filter=' . escapeshellarg((string) $input->getOption('filter'));
        }

        if ($input->getOption('group')) {
            $parts[] = '--group=' . escapeshellarg((string) $input->getOption('group'));
        }

        if ($input->getOption('exclude-group')) {
            $parts[] = '--exclude-group=' . escapeshellarg((string) $input->getOption('exclude-group'));
        }

        if ($input->getOption('coverage')) {
            $parts[] = '--coverage';
        }

        $command = implode(' ', $parts);

        return $command;
    }
}
