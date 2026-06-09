<?php

namespace Pinoox\Terminal\App;

use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppRouter;
use Pinoox\Portal\App\Domain;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:list',
    description: 'List installed apps with routes, domain hosts, and status',
    aliases: ['apps'],
)]

class AppListCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Shows every app under apps/ with enable state, URL routes, and domain hosts.

Examples:

  php pinoox app:list
  php pinoox app:list --enabled

HELP
            )
            ->addOption('enabled', null, InputOption::VALUE_NONE, 'Show only enabled apps');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $routes = AppRouter::routes();
        $hosts = Domain::hostMap();
        $enabledOnly = (bool) $input->getOption('enabled');

        $rows = [];

        foreach (AppEngine::all() as $package => $manager) {
            if (!$manager->exists()) {
                continue;
            }

            $config = $manager->config();
            $isEnabled = (bool) $config->get('enable');

            if ($enabledOnly && !$isEnabled) {
                continue;
            }

            $pathRoutes = array_keys(array_filter($routes, static fn(string $pkg): bool => $pkg === $package));
            $hostRoutes = [];

            foreach ($hosts as $pattern => $target) {
                $mapped = is_array($target)
                    ? (string) ($target['package'] ?? $target['app'] ?? '')
                    : (string) $target;

                if ($mapped === $package) {
                    $hostRoutes[] = $pattern;
                }
            }

            $rows[] = [
                $package,
                (string) ($config->get('name') ?: $package),
                $isEnabled ? 'yes' : 'no',
                $pathRoutes !== [] ? implode(', ', $pathRoutes) : '-',
                $hostRoutes !== [] ? implode(', ', $hostRoutes) : '-',
                (string) ($config->get('version-name') ?: '-'),
            ];
        }

        $output->writeln('');
        $output->writeln('<info>Installed apps</info>');

        if ($rows === []) {
            $output->writeln('<comment>No apps found.</comment>');
            $output->writeln('');

            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setStyle('box-double')
            ->setHeaders(['Package', 'Name', 'Enabled', 'Path routes', 'Domain hosts', 'Version'])
            ->setRows($rows);
        $table->render();
        $output->writeln('');

        return Command::SUCCESS;
    }
}

