<?php

namespace Pinoox\Terminal\Cache;

use Pinoox\Component\Cache\AppCacheManager;
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
    name: 'cache:build',
    description: 'Build runtime cache for routes, API, Twig, and other app stores',
    aliases: ['cb'],
)]

class CacheBuildCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Builds precomputed cache files under pinker/apps/{package}/cache/ for faster production loading.
Examples:
  php pinoox cache:build
  php pinoox cache:build com_my_shop
  php pinoox cache:build com_my_shop --only=routes --only=twig
  php pinoox cache:build --force
Stores: routes, api, boot, twig, graphql, pinker
HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp(allowAll: true))
            ->addOption('only', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Build only these stores (repeatable): routes, api, boot, twig, graphql, pinker')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Rebuild even when the cache is still fresh');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePackageRequired($input, $output, $io, [
            'allowAll' => true,
            'default' => 'all',
            'sectionTitle' => 'Build cache for',
        ]);
        $only = array_values(array_filter(array_map('trim', $input->getOption('only') ?? [])));
        $force = (bool) $input->getOption('force');
        $results = AppCacheManager::build(
            $package === 'all' ? null : $package,
            $only === [] ? null : $only,
            $force,
        );
        $rows = [];
        foreach ($results as $key => $ok) {
            [$pkg, $store] = array_pad(explode(':', $key, 2), 2, '');
            $rows[] = [$pkg, $store, $ok ? 'built' : 'skipped'];
        }
        $io->title('App Cache Build');
        $io->table(['Package', 'Store', 'Status'], $rows);
        return Command::SUCCESS;
    }
}

