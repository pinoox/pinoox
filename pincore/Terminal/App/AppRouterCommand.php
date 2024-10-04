<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Terminal\App;

use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppRouter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppRouterCommand extends Terminal
{
    protected function configure() : void
    {
        $this
            ->setName('app:router')
            ->setDescription('Manage application routes')
            ->addOption('package', 'p', InputOption::VALUE_OPTIONAL, 'Package name')
            ->addOption('path', 'u', InputOption::VALUE_OPTIONAL, 'Path name')
            ->addArgument('action', InputArgument::OPTIONAL, 'Action (set/remove)')
            ->addArgument('route', InputArgument::OPTIONAL, 'Route path')
            ->addArgument('packageName', InputArgument::OPTIONAL, 'Package name for set action');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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

    private function removeRoute(InputInterface $input, OutputInterface $output)
    {
        $route = $input->getArgument('route');
        AppRouter::delete($route);
        $output->writeln("<info>Route <options=bold>'$route'</> removed</info>");
    }


    private function setRoute(InputInterface $input, OutputInterface $output)
    {
        $route = $input->getArgument('route');
        $packageName = $input->getArgument('packageName');
        AppRouter::set($route, $packageName);
        $output->writeln("<info>Route <options=bold>'$route'</> set to package <options=bold>'$packageName'</></info>");
    }

    private function getRoutes(InputInterface $input, OutputInterface $output)
    {
        $routes = AppRouter::get();
        $output->writeln("");

        $output->writeln("All app routes:");

        $rows = array_map(fn(string $k, string $v): array => [$k, $v], array_keys($routes), array_values($routes));
        $this->printTable($output, $rows);
        $output->writeln("");

    }

    private function printTable(OutputInterface $output, $rows)
    {
        $table = new Table($output);
        $table->setStyle('box-double')
            ->setHeaders(['path', 'package'])
            ->setRows($rows);
        $table->render();
    }

    private function getRoutesByPackage(InputInterface $input, OutputInterface $output)
    {
        $package = $input->getOption('package');
        $routes = AppRouter::getByPackage($package);
        $output->writeln("");
        $output->writeln("Routes for package <fg=yellow>$package</>:");

        $rows = [];
        foreach ($routes as $route => $packageName) {
            $rows[] = [$route, $packageName];
        }
        $this->printTable($output,$rows);
        $output->writeln("");
    }

    private function getRoutesByPath(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getOption('path');
        $packageName = AppRouter::get($path);
        $output->writeln("");
        $output->writeln("Routes for path <fg=yellow>$path</>:");
        $rows = [
            [$path, $packageName]
        ];
        $this->printTable($output,$rows);
        $output->writeln("");

    }
}
