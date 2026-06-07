<?php

namespace Pinoox\PinDoc\GraphQL\Console;

use Pinoox\Component\Terminal;
use Pinoox\PinDoc\AppDocProfile;
use Pinoox\PinDoc\Console\ResolvesDocOutputPath;
use Pinoox\PinDoc\PinDocMarkdownLoader;
use Pinoox\PinDoc\GraphQL\GraphQLDocsGenerator;
use Pinoox\PinDoc\GraphQL\GraphQLRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'graphql:docs',
    description: 'Generate GraphQL schema docs (Markdown or HTML) for an app',
)]

class GraphQLDocsCommand extends Terminal
{
    use SelectsGraphQLPackage;
    use ResolvesDocOutputPath;

    protected function configure(): void
    {
        $this
            ->addOption('package', 'p', InputOption::VALUE_OPTIONAL, 'App package with GraphQL schema. Leave empty to pick from the list.')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Documentation format: md or html', 'md')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path (absolute, project-relative, or app-relative)')
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
            $package = $this->resolveGraphQLPackage($input, $output, $io);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $audience = $input->getOption('audience') ? (string)$input->getOption('audience') : null;

        $generator = new GraphQLDocsGenerator();
        $extraMarkdown = null;

        if ($format === 'html') {
            $entry = (new GraphQLRegistry())->all($package)[$package] ?? null;
            $appMeta = is_array($entry['app_meta'] ?? null)
                ? $entry['app_meta']
                : AppDocProfile::fromPackage($package);
            $docs = AppDocProfile::resolveDocs(
                is_array($entry['docs'] ?? null) ? $entry['docs'] : [],
                $appMeta,
                'graphql',
            );
            $extraMarkdown = (new PinDocMarkdownLoader())->resolveForPackage(
                $package,
                $docs,
                $input->getOption('with-md') ? (string)$input->getOption('with-md') : null,
            );
            $extraMarkdown = $extraMarkdown !== '' ? $extraMarkdown : null;
        }

        try {
            $content = $generator->generate($format, $package, $audience, $extraMarkdown);
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
        $this->success('GraphQL documentation generated for ' . $package . ': ' . $path);

        return Command::SUCCESS;
    }
}

