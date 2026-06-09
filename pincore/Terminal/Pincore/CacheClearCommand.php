<?php

namespace Pinoox\Terminal\Pincore;

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
    name: 'cache:clear',
    description: 'Clear app runtime cache (routes, API, Twig, GraphQL, Pinker)',
    aliases: ['cc'],
)]

class CacheClearCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Removes cached files from pinker/apps/{package}/cache/.

Examples:

  php pinoox cache:clear

  php pinoox cache:clear com_my_shop

  php pinoox cache:clear com_my_shop --only=twig

HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp(allowAll: true))
            ->addOption('only', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Clear only these stores: routes, api, boot, twig, graphql, pinker');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePackageRequired($input, $output, $io, [
            'allowAll' => true,
            'default' => 'all',
            'sectionTitle' => 'Clear cache for',
        ]);
        $only = array_values(array_filter(array_map('trim', $input->getOption('only') ?? [])));

        AppCacheManager::clear(
            $package === 'all' ? null : $package,
            $only === [] ? null : $only,
        );

        $io->success('App cache cleared.');

        return Command::SUCCESS;
    }
}

