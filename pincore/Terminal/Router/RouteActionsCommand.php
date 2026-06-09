<?php

namespace Pinoox\Terminal\Router;

use Pinoox\Component\Router\Action\ActionCache;
use Pinoox\Component\Router\Action\ActionCatalog;
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
    name: 'route:actions',
    description: 'List, validate, and inspect named router actions (@index, @api, …)',
    aliases: ['routes'],
)]

class RouteActionsCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Shows named actions registered in routes/actions.php files.
Examples:
  php pinoox route:actions
  php pinoox route:actions com_my_shop
  php pinoox route:actions --validate
  php pinoox route:actions --cache
HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp(optional: true))
            ->addOption('validate', null, InputOption::VALUE_NONE, 'Check that every action reference is valid')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Treat unused actions as validation errors')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print results as JSON')
            ->addOption('cache', null, InputOption::VALUE_NONE, 'Show route action cache file paths');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePackageFilter($input, $output, $io, [
            'sectionTitle' => 'Filter actions by',
        ]);
        $catalog = new ActionCatalog();
        if ($input->getOption('validate')) {
            return $this->runValidation($io, $catalog, $package, (bool) $input->getOption('strict'));
        }
        if ($input->getOption('cache')) {
            return $this->showCachePaths($io, $package);
        }
        $actions = $catalog->all($package);
        if ($actions === []) {
            $io->success('No named actions found.');
            return Command::SUCCESS;
        }
        if ($input->getOption('json')) {
            $io->writeln(json_encode($actions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }
        $rows = array_map(static function (array $action) {
            return [
                $action['package'] ?? '',
                $action['name'] ?? '',
                $action['handler'] ?? '',
                implode(', ', (array) ($action['routes'] ?? [])) ?: '—',
                $action['file'] ?? '—',
                ($action['used'] ?? false) ? 'yes' : 'no',
                $action['description'] ?? '',
            ];
        }, $actions);
        $io->title('Pinoox Router Actions');
        $io->table(['Package', 'Action', 'Handler', 'Used by routes', 'Defined in', 'Used', 'Description'], $rows);
        return Command::SUCCESS;
    }

    private function runValidation(SymfonyStyle $io, ActionCatalog $catalog, ?string $package, bool $strict): int
    {
        $errors = $catalog->validate($package, $strict);
        $critical = array_values(array_filter(
            $errors,
            static fn (string $error) => !str_contains($error, 'not referenced'),
        ));
        if ($errors === []) {
            $io->success('All router actions are valid.');
            return Command::SUCCESS;
        }
        $io->section('Validation results');
        $io->listing($errors);
        return $critical === [] ? Command::SUCCESS : Command::FAILURE;
    }

    private function showCachePaths(SymfonyStyle $io, ?string $package): int
    {
        $packages = $package !== null ? [$package] : array_keys($this->packageChoices());
        $rows = [];
        foreach ($packages as $pkg) {
            if (!$this->packageExists($pkg)) {
                continue;
            }
            $path = ActionCache::path($pkg);
            $rows[] = [$pkg, $path, is_file($path) ? 'yes' : 'no'];
        }
        $io->table(['Package', 'Cache file', 'Exists'], $rows);
        return Command::SUCCESS;
    }
}

