<?php

namespace Pinoox\Terminal\App;

use Pinoox\Component\Terminal;
use Pinoox\Portal\App\Domain;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:domain',
    description: 'View host-to-app mappings from domain.config.php',
)]

class AppDomainCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Shows host and wildcard subdomain mappings from pincore/config/domain.config.php.

Domain routing is evaluated before path routes in app-router.config.php.

Examples:

  php pinoox app:domain
  php pinoox app:domain --host shop.localhost
  php pinoox app:domain --package com_my_shop

HELP
            )
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Resolve one host and show the matched app')
            ->addOption('package', 'p', InputOption::VALUE_OPTIONAL, 'Filter hosts mapped to one app package');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $host = $input->getOption('host');
        $packageFilter = $this->readPackageInput($input, 'package', ['package']);

        if (is_string($host) && $host !== '') {
            $this->resolveHost($output, $host);

            return Command::SUCCESS;
        }

        if ($packageFilter === '' && $input->isInteractive() && $input->getOption('package') === null) {
            $io = new SymfonyStyle($input, $output);
            $answer = $io->confirm('Filter by app package?', false);

            if ($answer) {
                $packageFilter = $this->resolvePackageFilter($input, $output, $io, [
                    'appsOnly' => true,
                    'sectionTitle' => 'Filter domain hosts by app',
                ]) ?? '';
            }
        }

        $this->listHosts($output, $packageFilter !== '' ? $packageFilter : null);

        return Command::SUCCESS;
    }

    private function listHosts(OutputInterface $output, ?string $packageFilter = null): void
    {
        $default = Domain::defaultHost();
        $map = Domain::hostMap();

        if ($packageFilter !== null) {
            $map = array_filter($map, function (mixed $target) use ($packageFilter): bool {
                $mapped = is_array($target)
                    ? (string) ($target['package'] ?? $target['app'] ?? '')
                    : (string) $target;

                return $mapped === $packageFilter;
            });
        }

        $output->writeln('');
        $output->writeln('<info>Domain routing</info>');

        if ($packageFilter !== null) {
            $output->writeln('Filtered by package: <fg=yellow>' . $packageFilter . '</>');
        }

        if (is_string($default) && $default !== '') {
            $output->writeln('Canonical default domain: <fg=yellow>' . $default . '</>');
        }

        $output->writeln('<comment>Any unlisted host is treated as the default domain (path routing).</comment>');

        if ($map === []) {
            $output->writeln('<comment>No host mappings configured.</comment>');
            $output->writeln('');

            return;
        }

        $rows = [];
        foreach ($map as $pattern => $target) {
            $rows[] = [
                $pattern,
                is_array($target) ? (string)($target['package'] ?? $target['app'] ?? '') : (string)$target,
                is_array($target) ? json_encode(array_diff_key($target, ['package' => 1, 'app' => 1])) : '',
            ];
        }

        $table = new Table($output);
        $table->setStyle('box-double')
            ->setHeaders(['host', 'package', 'options'])
            ->setRows($rows);
        $table->render();
        $output->writeln('');
    }

    private function resolveHost(OutputInterface $output, string $host): void
    {
        $normalized = Domain::normalizeHost($host);
        $match = Domain::match($normalized);

        $output->writeln('');
        $output->writeln("Host <fg=yellow>{$normalized}</> resolves to:");

        if ($match === null) {
            $resolution = Domain::___()->resolve($normalized);

            if ($resolution['mode'] === 'default') {
                $output->writeln('<info>Default domain</info> — uses path routes from app-router.config.php');

                if (is_string($resolution['canonical_default']) && $resolution['canonical_default'] !== '') {
                    $output->writeln('Canonical default host: <fg=yellow>' . $resolution['canonical_default'] . '</>');
                }

                $output->writeln('<comment>Only hosts listed in domain.config.php get a dedicated app mapping.</comment>');
            } else {
                $output->writeln('<comment>No domain mapping matched.</comment>');
            }

            $output->writeln('');

            return;
        }

        $rows = [
            ['package', $match->package],
            ['path', $match->path],
            ['subdomain', $match->subdomain ?? '-'],
            ['pattern', $match->pattern ?? $normalized],
        ];

        $table = new Table($output);
        $table->setStyle('box-double')
            ->setHeaders(['key', 'value'])
            ->setRows($rows);
        $table->render();
        $output->writeln('');
    }
}

