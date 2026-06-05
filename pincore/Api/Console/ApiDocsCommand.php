<?php

namespace Pinoox\Api\Console;

use Pinoox\Api\ApiDocsGenerator;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Terminal;
use Pinoox\Support\SystemConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'api:docs',
    description: 'Generate REST API documentation.',
)]
class ApiDocsCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Documentation format: md or html', 'md')
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Output file path')
            ->addOption('app', null, InputOption::VALUE_OPTIONAL, 'Filter by app package')
            ->addOption('api-version', null, InputOption::VALUE_OPTIONAL, 'Filter by API version');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $content = (new ApiDocsGenerator())->generate(
            (string)$input->getOption('format'),
            $input->getOption('app') ?: null,
            $input->getOption('api-version') ?: null,
        );

        $path = $input->getOption('output');
        if ($path) {
            $path = $this->resolveOutputPath((string)$path);
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($path, $content);
            $this->success('API documentation generated: ' . $path);
        } else {
            $output->writeln($content);
        }

        return Command::SUCCESS;
    }

    private function resolveOutputPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        if (preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, '~')) {
            return SystemConfig::resolvePath($path);
        }

        return rtrim(str_replace('\\', '/', Loader::getBasePath()), '/') . '/' . ltrim($path, '/');
    }
}
