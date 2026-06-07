<?php

namespace Pinoox\Terminal\App;

use Pinoox\Component\Http\Request;
use Pinoox\Component\Package\AppRouter as AppRouterComponent;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppRouter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:resolve',
    description: 'Show which app handles a URL path and/or host',
)]

class AppResolveCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Simulates AppRouter resolution for debugging domain and path routing.

Examples:

  php pinoox app:resolve --path /manager
  php pinoox app:resolve --host manager.localhost
  php pinoox app:resolve --host localhost --path /manager/dashboard

HELP
            )
            ->addOption('path', 'u', InputOption::VALUE_OPTIONAL, 'Request path (e.g. /manager/dashboard)', '/')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'HTTP host (e.g. shop.localhost)', 'localhost');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $path = (string) $input->getOption('path');
        $host = (string) $input->getOption('host');
        $pathInfo = trim(parse_url($path, PHP_URL_PATH) ?: $path, '/');

        $request = Request::create(
            'http://' . $host . '/' . ltrim($pathInfo, '/'),
            'GET',
            [],
            [],
            [],
            ['HTTP_HOST' => $host],
        );

        $router = new AppRouterComponent(
            AppRouter::config(),
            AppEngine::___(),
            $request,
        );

        $layer = $router->find($pathInfo);

        $output->writeln('');
        $output->writeln('<info>App resolution</info>');

        $table = new Table($output);
        $table->setStyle('box-double')
            ->setHeaders(['Key', 'Value'])
            ->setRows([
                ['host', $host],
                ['path', $pathInfo === '' ? '/' : '/' . $pathInfo],
                ['package', (string) $layer->getPackageName()],
                ['app base path', $layer->getPath()],
                ['matched by', (string) ($layer->matchedBy() ?? '-')],
                ['subdomain', (string) ($layer->subdomain() ?? '-')],
            ]);
        $table->render();
        $output->writeln('');

        return Command::SUCCESS;
    }
}

