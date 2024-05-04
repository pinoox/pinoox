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
    protected function configure()
    {
        $this
            ->setName('app:router')
            ->setDescription('Manage application routes')
            ->addOption('package', 'p',InputOption::VALUE_OPTIONAL, 'Package name')
            ->addArgument('action', InputArgument::OPTIONAL, 'Action (set/remove)')
            ->addArgument('route', InputArgument::OPTIONAL, 'Route path')
            ->addArgument('packageName', InputArgument::OPTIONAL, 'Package name for set action');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $package = $input->getOption('package');
        $action = $input->getArgument('action');

        if ($action === 'set') {
            $this->setRoute($input, $output);
        } elseif ($action === 'remove') {
            $this->removeRoute($input, $output);
        } elseif ($package) {
            $this->getRoutesByPackage($input, $output);
        } else {
            $this->getRoutes($input, $output);
        }

        return Command::SUCCESS;
    }

    private function removeRoute(InputInterface $input, OutputInterface $output)
    {
        $route = $input->getArgument('route');
        AppRouter::delete($route);
        $output->writeln("Route '$route' removed");
    }


    private function setRoute(InputInterface $input, OutputInterface $output)
    {
        $route = $input->getArgument('route');
        $packageName = $input->getArgument('packageName');
        AppRouter::set($route, $packageName);
        $output->writeln("Route '$route' set to package '$packageName'");
    }

    private function getRoutes(InputInterface $input, OutputInterface $output)
    {
        $routes = AppRouter::get();
        $output->writeln("All app routes:");

        $rows = array_map(fn(string $k, string $v): array => [$k, $v], array_keys($routes), array_values($routes));
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
        $output->writeln("Routes for package '$package':");

        $rows = [];
        $i = 1;
        foreach ($routes as $route => $packageName) {
            $rows[] = [$i++, $route];
        }
        $table = new Table($output);
        $table->setStyle('box-double')
            ->setHeaders(['#', 'path'])
            ->setRows($rows)
            ->render();
    }
}
