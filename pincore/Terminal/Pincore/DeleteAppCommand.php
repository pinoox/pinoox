<?php

namespace Pinoox\Terminal\Pincore;

use Pinoox\Portal\FileSystem;
use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'app:delete',
    description: 'Delete a Pinoox application and its route configurations'
)]
class DeleteAppCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'Package name to delete (e.g. com_my_app)')
            ->addOption('route-only', 'r', InputOption::VALUE_NONE, 'Only delete routes, keep the app directory')
            ->addOption('specific-route', 's', InputOption::VALUE_OPTIONAL, 'Specify a specific route to remove');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $packageName = $input->getArgument('package');
        $appDir = 'apps/' . $packageName;
        $routeOnly = $input->getOption('route-only');
        $specificRoute = $input->getOption('specific-route');

        // Check if the app exists unless we're only removing routes
        if (!$routeOnly && !FileSystem::exists($appDir)) {
            $output->writeln("<error>App '{$packageName}' does not exist!</error>");
            return Command::FAILURE;
        }

        // If specific route is provided, ensure it starts with a slash
        if ($specificRoute !== null && substr($specificRoute, 0, 1) !== '/') {
            $specificRoute = '/' . $specificRoute;
            $output->writeln("<comment>Added leading slash to route: {$specificRoute}</comment>");
        }

        // Ask for confirmation
        $helper = $this->getHelper('question');
        $actionDesc = $routeOnly ? "remove routes for" : "delete";
        
        $confirmMessage = $specificRoute 
            ? "Are you sure you want to remove the route '{$specificRoute}' for '{$packageName}'? (y/N) "
            : "Are you sure you want to {$actionDesc} '{$packageName}'? This action cannot be undone. (y/N) ";
            
        $question = new ConfirmationQuestion($confirmMessage, false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln("<info>Operation cancelled.</info>");
            return Command::SUCCESS;
        }

        // Handle routes
        $this->handleRoutes($output, $packageName, $specificRoute);

        // Delete the app directory if not route-only mode
        if (!$routeOnly) {
            try {
                FileSystem::remove($appDir);
                $output->writeln("<info>App '{$packageName}' has been successfully deleted.</info>");
            } catch (\Exception $e) {
                $output->writeln("<error>Failed to delete app '{$packageName}': {$e->getMessage()}</error>");
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
    
    /**
     * Handle the removal of routes
     */
    private function handleRoutes(OutputInterface $output, string $packageName, ?string $specificRoute = null): void
    {
        $bakedRouter = PINOOX_CORE_PATH . 'pinker/config/app/router.config.php';
        if (!FileSystem::exists($bakedRouter)) {
            $output->writeln("<comment>Pinker router configuration file not found.</comment>");
            return;
        }
        
        $routes = include $bakedRouter;
        $routesUpdated = false;

        // Find and remove routes
        foreach ($routes as $path => $package) {
            // If specific route is provided, only remove that one
            if ($specificRoute !== null) {
                if ($path === $specificRoute && $package === $packageName) {
                    unset($routes[$path]);
                    $output->writeln("<info>Route '{$path}' removed.</info>");
                    $routesUpdated = true;
                    break; // Exit after removing the specific route
                }
            } elseif ($package === $packageName) {
                // Remove all routes for this package
                unset($routes[$path]);
                $output->writeln("<info>Route '{$path}' removed.</info>");
                $routesUpdated = true;
            }
        }

        if ($routesUpdated) {
            // Write the updated routes back to the config file
            $export = "<?php\n\nreturn [\n";
            foreach ($routes as $path => $pkg) {
                $export .= "    '{$path}' => '{$pkg}',\n";
            }
            $export .= "];\n";
            FileSystem::dumpFile($bakedRouter, $export);
            $output->writeln("<info>Router configuration updated.</info>");
        } else {
            if ($specificRoute !== null) {
                $output->writeln("<comment>Route '{$specificRoute}' for '{$packageName}' not found in Pinker configuration.</comment>");
            } else {
                $output->writeln("<comment>No routes found for '{$packageName}' in Pinker configuration.</comment>");
            }
        }
    }
} 