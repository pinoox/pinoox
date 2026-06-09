<?php

namespace Pinoox\Terminal\Deps;

use Pinoox\Component\Deps\DependencyInstallOptions;
use Pinoox\Component\Deps\DependencyInstaller;
use Pinoox\Component\Deps\DependencyRunResult;
use Pinoox\Component\Deps\DependencyScanner;
use Pinoox\Component\Deps\DependencyTarget;
use Pinoox\Component\Terminal;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'deps',
    description: 'Install, update, and inspect Composer and npm dependencies across the project',
    aliases: ['dep'],
)]

class DepsCommand extends Terminal
{
    use SelectsPackage;

    private DependencyScanner $scanner;

    private DependencyInstaller $installer;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Manage Composer (PHP) and npm (theme frontend) dependencies for the whole project or a single app.

Actions:
  status    List discovered manifests and whether vendor/node_modules exist
  install   Run composer install / npm install (or npm ci when lockfile exists)
  update    Run composer update / npm update

Scopes:
  all         Project root + every app composer.json and theme package.json
  platform    Project root composer.json only
  com_my_shop Single app composer.json + active theme package.json

Examples:
  php pinoox deps status all
  php pinoox deps install platform
  php pinoox deps install com_pinoox_manager
  php pinoox deps install com_pinoox_manager --all-themes
  php pinoox deps install all --production
  php pinoox deps update com_my_shop --composer-only

Documentation: docs/pinoox-deps.md
HELP
            )
            ->addArgument('action', InputArgument::REQUIRED, 'Action: status, install, update')
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp(allowAll: true))
            ->addOption('composer-only', null, InputOption::VALUE_NONE, 'Only run Composer targets')
            ->addOption('npm-only', null, InputOption::VALUE_NONE, 'Only run npm targets')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Theme folder name (defaults to app.php theme)')
            ->addOption('all-themes', null, InputOption::VALUE_NONE, 'Include every theme with package.json in the app')
            ->addOption('production', null, InputOption::VALUE_NONE, 'Composer: install/update without dev dependencies')
            ->addOption('no-ci', null, InputOption::VALUE_NONE, 'npm: use install instead of ci when package-lock.json exists')
            ->addOption('plain', null, InputOption::VALUE_NONE, 'Plain output without step panels (CI-friendly)')
            ->addOption('continue-on-error', null, InputOption::VALUE_NONE, 'Continue remaining targets when one step fails');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $presenter = new DepsConsolePresenter($io, $output, (bool) $input->getOption('plain'));
        $action = strtolower(trim((string) $input->getArgument('action')));

        if (!in_array($action, ['status', 'install', 'update'], true)) {
            $io->error('Unknown action "' . $action . '". Use status, install, or update.');

            return Command::FAILURE;
        }

        if ((bool) $input->getOption('composer-only') && (bool) $input->getOption('npm-only')) {
            $io->error('Use only one of --composer-only or --npm-only.');

            return Command::FAILURE;
        }

        $this->scanner = new DependencyScanner();
        $this->installer = new DependencyInstaller();

        $package = $this->resolvePackageRequired($input, $output, $io, [
            'allowAll' => true,
            'default' => 'all',
            'sectionTitle' => 'Dependency scope',
        ]);

        $typeFilter = $this->resolveTypeFilter($input);
        $targets = $this->scanner->discover(
            scope: $package,
            themeName: $input->getOption('theme') ? (string) $input->getOption('theme') : null,
            allThemes: (bool) $input->getOption('all-themes'),
            typeFilter: $typeFilter,
        );

        if ($targets === []) {
            $io->warning('No composer.json or package.json targets were found for scope: ' . $package);

            return Command::SUCCESS;
        }

        return match ($action) {
            'status' => $this->runStatus($presenter, $package, $targets),
            'install' => $this->runInstall($presenter, $package, $targets, $input),
            'update' => $this->runUpdate($presenter, $package, $targets, $input),
        };
    }

    /**
     * @param list<DependencyTarget> $targets
     */
    private function runStatus(DepsConsolePresenter $presenter, string $scope, array $targets): int
    {
        $presenter->renderHeader('status', $scope, $targets);
        $presenter->renderStatusBoard($targets);

        return Command::SUCCESS;
    }

    /**
     * @param list<DependencyTarget> $targets
     */
    private function runInstall(DepsConsolePresenter $presenter, string $scope, array $targets, InputInterface $input): int
    {
        return $this->runDependencyAction($presenter, $scope, $targets, $input, 'install');
    }

    /**
     * @param list<DependencyTarget> $targets
     */
    private function runUpdate(DepsConsolePresenter $presenter, string $scope, array $targets, InputInterface $input): int
    {
        return $this->runDependencyAction($presenter, $scope, $targets, $input, 'update');
    }

    /**
     * @param list<DependencyTarget> $targets
     */
    private function runDependencyAction(
        DepsConsolePresenter $presenter,
        string $scope,
        array $targets,
        InputInterface $input,
        string $action,
    ): int {
        $options = new DependencyInstallOptions(
            production: (bool) $input->getOption('production'),
            npmCi: !(bool) $input->getOption('no-ci'),
        );

        $presenter->renderHeader($action, $scope, $targets);
        $presenter->renderPlan($action, $targets);

        $results = $presenter->runWorkflow(
            $action,
            $targets,
            function (DependencyTarget $target, callable $onOutput) use ($action, $options): DependencyRunResult {
                return $action === 'install'
                    ? $this->installer->install($target, $options, $onOutput)
                    : $this->installer->update($target, $options, $onOutput);
            },
            continueOnError: (bool) $input->getOption('continue-on-error'),
        );

        $exitCode = $presenter->renderFinalSummary($action, $results);

        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function resolveTypeFilter(InputInterface $input): ?string
    {
        if ((bool) $input->getOption('composer-only')) {
            return 'composer';
        }

        if ((bool) $input->getOption('npm-only')) {
            return 'npm';
        }

        return null;
    }
}
