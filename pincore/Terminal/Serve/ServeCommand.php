<?php

namespace Pinoox\Terminal\Serve;

use Pinoox\Component\Server\DevelopmentServer;
use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'serve',
    description: 'Start the Pinoox development web server (PHP built-in)',
)]
class ServeCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Starts a local HTTP server for development — similar to Laravel's `php artisan serve`.

Examples:
  php pinoox serve
  php pinoox serve --port=8080
  php pinoox serve --host=0.0.0.0 --port=9000
  php pinoox serve --open

Environment (.env):
  SERVER_HOST=127.0.0.1
  SERVER_PORT=8000

The server uses system/launcher/server.php as a router (same rules as .htaccess).
HELP
            )
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host address (default from SERVER_HOST or 127.0.0.1)')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port number (default from SERVER_PORT or 8000)')
            ->addOption('tries', null, InputOption::VALUE_OPTIONAL, 'How many ports to try if the default is busy', 10)
            ->addOption('no-reload', null, InputOption::VALUE_NONE, 'Do not restart when .env changes')
            ->addOption('open', 'o', InputOption::VALUE_NONE, 'Open the site in your default browser after start');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $host = $this->resolveHost((string) ($input->getOption('host') ?: _env('SERVER_HOST', '127.0.0.1')));
        $port = $this->normalizePort($input->getOption('port') ?? _env('SERVER_PORT'));
        $tries = max(1, (int) $input->getOption('tries'));
        $documentRoot = rtrim(str_replace('\\', '/', (string) PINOOX_BASE_PATH), '/');
        $router = DevelopmentServer::defaultRouterScript();

        if (!is_file($documentRoot . '/index.php')) {
            $io->error('index.php was not found in the project root: ' . $documentRoot);

            return Command::FAILURE;
        }

        if (!is_file($router)) {
            $io->error('Router script not found: ' . $router);

            return Command::FAILURE;
        }

        $server = new DevelopmentServer(
            host: $host,
            explicitPort: $port,
            maxTries: $tries,
            noReload: (bool) $input->getOption('no-reload'),
            documentRoot: $documentRoot,
            routerScript: $router,
            output: $output,
        );

        if ((bool) $input->getOption('open')) {
            $this->openBrowser($server->url());
        }

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, static function (): void {
                exit(0);
            });
            pcntl_signal(SIGTERM, static function (): void {
                exit(0);
            });
        }

        return $server->run();
    }

    private function resolveHost(string $host): string
    {
        $host = trim($host);

        if ($host === '') {
            return '127.0.0.1';
        }

        if (preg_match('/^\[(.+)]:(\d+)$/', $host, $matches) === 1) {
            return '[' . $matches[1] . ']';
        }

        if (preg_match('/^(.+):(\d+)$/', $host, $matches) === 1 && !str_contains($host, '::')) {
            return $matches[1];
        }

        return $host;
    }

    private function normalizePort(mixed $port): ?int
    {
        if ($port === null || $port === '') {
            return null;
        }

        $port = (int) $port;

        return $port > 0 ? $port : null;
    }

    private function openBrowser(string $url): void
    {
        $os = PHP_OS_FAMILY;

        $command = match ($os) {
            'Windows' => 'start "" ' . escapeshellarg($url),
            'Darwin' => 'open ' . escapeshellarg($url),
            default => 'xdg-open ' . escapeshellarg($url),
        };

        @exec($command);
    }
}
