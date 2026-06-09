<?php

namespace Pinoox\Terminal\App;

use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppRouter;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:router',
    aliases: ['router'],
    description: 'View or edit URL-to-app mappings in app-router.config.php',
)]

class AppRouterCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Maps a URL path to an app package (which app handles /shop, /panel, etc.).

Path routes live in pincore/config/app-router.config.php.
Host/domain routes live in pincore/config/domain.config.php and are checked first.

Examples:

  php pinoox app:router
  php pinoox app:domain
  php pinoox app:router -p com_my_shop
  php pinoox app:router set /shop com_my_shop
  php pinoox app:router remove /shop

HELP
            )
            ->addOption('package', 'p', InputOption::VALUE_OPTIONAL, 'Show routes for one app package')
            ->addOption('path', 'u', InputOption::VALUE_OPTIONAL, 'Show which app handles one URL path')
            ->addArgument('action', InputArgument::OPTIONAL, 'set or remove')
            ->addArgument('route', InputArgument::OPTIONAL, 'URL path (e.g. /shop)')
            ->addArgument('packageName', InputArgument::OPTIONAL, 'Target app package when using set');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $input->getOption('package');
        $path = $input->getOption('path');
        $action = $input->getArgument('action');

        if ($action === 'set') {
            $this->setRoute($input, $output);
        } elseif ($action === 'remove') {
            $this->removeRoute($input, $output);
        } elseif ($package) {
            $this->getRoutesByPackage($input, $output);
        } elseif ($path) {
            $this->getRoutesByPath($input, $output);
        } else {
            $this->getRoutes($input, $output);
        }

        return Command::SUCCESS;
    }

    private function removeRoute(InputInterface $input, OutputInterface $output): void
    {
        $route = $input->getArgument('route');
        AppRouter::delete($route);
        $output->writeln("<info>Route <options=bold>'$route'</> removed</info>");
    }

    private function setRoute(InputInterface $input, OutputInterface $output): void
    {
        $route = $input->getArgument('route');
        $packageName = $input->getArgument('packageName');

        if (!$packageName && $input->isInteractive()) {
            $io = new SymfonyStyle($input, $output);
            $packageName = $this->resolvePackageRequired($input, $output, $io, [
                'appsOnly' => true,
                'sectionTitle' => 'Assign route to app',
            ]);
        }

        AppRouter::set($route, $packageName);
        $output->writeln("<info>Route <options=bold>'$route'</> set to package <options=bold>'$packageName'</></info>");
    }

    private function getRoutes(InputInterface $input, OutputInterface $output): void
    {
        $routes = AppRouter::get();
        $output->writeln('');
        $output->writeln('All app routes:');

        $rows = array_map(fn (string $k, string $v): array => [$k, $v], array_keys($routes), array_values($routes));
        $this->printTable($output, $rows);
        $output->writeln('');
    }

    private function printTable(OutputInterface $output, $rows): void
    {
        $table = new Table($output);
        $table->setStyle('box-double')
            ->setHeaders(['path', 'package'])
            ->setRows($rows);
        $table->render();
    }

    private function getRoutesByPackage(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $package = $input->getOption('package');

        if (!$package && $input->isInteractive()) {
            $package = $this->resolvePackageRequired($input, $output, $io, [
                'sectionTitle' => 'Show routes for',
            ]);
        }

        $routes = AppRouter::getByPackage($package);
        $output->writeln('');
        $output->writeln("Routes for package <fg=yellow>$package</>:");

        $rows = [];
        foreach ($routes as $route => $packageName) {
            $rows[] = [$route, $packageName];
        }
        $this->printTable($output, $rows);
        $output->writeln('');
    }

    private function getRoutesByPath(InputInterface $input, OutputInterface $output): void
    {
        $path = $input->getOption('path');
        $packageName = AppRouter::get($path);
        $output->writeln('');
        $output->writeln("Routes for path <fg=yellow>$path</>:");
        $rows = [
            [$path, $packageName],
        ];
        $this->printTable($output, $rows);
        $output->writeln('');
    }
}

