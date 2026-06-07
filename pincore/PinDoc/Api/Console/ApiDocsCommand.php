<?php

namespace Pinoox\PinDoc\Api\Console;

use Pinoox\PinDoc\Api\ApiDocsGenerator;
use Pinoox\PinDoc\Api\AppApiRegistry;
use Pinoox\Component\Terminal;
use Pinoox\PinDoc\AppDocProfile;
use Pinoox\PinDoc\Console\ResolvesDocOutputPath;
use Pinoox\PinDoc\PinDocMarkdownLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'api:docs',
    description: 'Generate REST API docs (Markdown or HTML) for an app',
)]

class ApiDocsCommand extends Terminal
{
    use SelectsApiPackage;
    use ResolvesDocOutputPath;

    protected function configure(): void
    {
        $this
            ->addOption('package', 'p', InputOption::VALUE_OPTIONAL, 'App package with routes/api.php. Leave empty to pick from the list.')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Documentation format: md or html', 'md')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path (absolute, project-relative, or app-relative)')
            ->addOption('api-version', null, InputOption::VALUE_OPTIONAL, 'Filter by API version')
            ->addOption('audience', null, InputOption::VALUE_OPTIONAL, 'Doc audience: external (default) or internal')
            ->addOption('with-md', null, InputOption::VALUE_OPTIONAL, 'Merge Markdown file into HTML overview (app-relative path)')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Print documentation to the terminal instead of saving a file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $format = strtolower((string)$input->getOption('format'));

        if (!in_array($format, ['md', 'html'], true)) {
            $this->error('Unsupported format. Use md or html.');

            return Command::FAILURE;
        }

        try {
            $package = $this->resolveApiPackage($input, $output, $io);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $audience = $input->getOption('audience') ? (string)$input->getOption('audience') : null;

        $generator = new ApiDocsGenerator();
        $extraMarkdown = null;

        if ($format === 'html') {
            $registry = new AppApiRegistry();
            $entry = $registry->firstEntry($registry->all($package, $input->getOption('api-version') ?: null), $package);
            $appMeta = is_array($entry['app_meta'] ?? null)
                ? $entry['app_meta']
                : AppDocProfile::fromPackage($package);
            $docs = AppDocProfile::resolveDocs(
                is_array($entry['docs'] ?? null) ? $entry['docs'] : [],
                $appMeta,
                'rest',
            );
            $extraMarkdown = (new PinDocMarkdownLoader())->resolveForPackage(
                $package,
                $docs,
                $input->getOption('with-md') ? (string)$input->getOption('with-md') : null,
            );
            $extraMarkdown = $extraMarkdown !== '' ? $extraMarkdown : null;
        }

        try {
            $content = $generator->generate(
                $format,
                $package,
                $input->getOption('api-version') ?: null,
                $audience,
                $extraMarkdown,
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($input->getOption('stdout')) {
            $output->writeln($content);

            return Command::SUCCESS;
        }

        $path = $this->resolveDocOutputPath(
            $package,
            $format,
            $generator,
            $input->getOption('output') ? (string)$input->getOption('output') : null,
            $audience,
        );

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        $this->success('API documentation generated for ' . $package . ': ' . $path);

        return Command::SUCCESS;
    }
}

