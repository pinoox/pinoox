<?php

namespace Pinoox\PinDoc\Console;

use Pinoox\PinDoc\Api\ApiDocsGenerator;
use Pinoox\PinDoc\Api\AppApiRegistry;
use Pinoox\PinDoc\Api\Console\SelectsApiPackage;
use Pinoox\PinDoc\AppDocProfile;
use Pinoox\PinDoc\PinDocHtmlBuilder;
use Pinoox\PinDoc\PinDocMarkdownLoader;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pindoc:html',
    description: 'Build PinDoc HTML from API docs and/or custom Markdown.',
)]

class PinDocHtmlCommand extends Terminal
{
    use SelectsApiPackage;
    use ResolvesDocOutputPath;

    protected function configure(): void
    {
        $this
            ->addOption('package', 'p', InputOption::VALUE_OPTIONAL, 'App package name (for API docs shell). Leave empty to pick from the list.')
            ->addOption('input', 'i', InputOption::VALUE_OPTIONAL, 'Markdown file path')
            ->addOption('with-md', null, InputOption::VALUE_OPTIONAL, 'Extra Markdown merged into API overview')
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'Build mode: api (default) or prose', 'api')
            ->addOption('title', null, InputOption::VALUE_OPTIONAL, 'Page title for prose mode')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output HTML path')
            ->addOption('api-version', null, InputOption::VALUE_OPTIONAL, 'Filter REST API version')
            ->addOption('audience', null, InputOption::VALUE_OPTIONAL, 'Doc audience: external or internal')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Print HTML to the terminal');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $mode = strtolower((string)$input->getOption('mode'));
        $package = trim((string)($input->getOption('package') ?: ''));
        $inputPath = trim((string)($input->getOption('input') ?: ''));
        $withMd = trim((string)($input->getOption('with-md') ?: ''));
        $builder = new PinDocHtmlBuilder();

        try {
            if ($mode === 'prose') {
                if ($inputPath === '') {
                    throw new \InvalidArgumentException('Prose mode requires --input markdown file.');
                }

                $content = $builder->fromMarkdownFile(
                    $this->resolveMarkdownPath($package, $inputPath),
                    (string)($input->getOption('title') ?: 'Documentation'),
                    '',
                    $package !== '' ? $package : null,
                );
            } else {
                if ($package === '') {
                    $package = $this->resolveApiPackage($input, $output, $io);
                } elseif (!$this->apiPackageExists($package)) {
                    throw new \InvalidArgumentException("Package '{$package}' was not found or has no API routes.");
                }

                $markdownPath = $withMd !== '' ? $withMd : ($inputPath !== '' ? $inputPath : null);
                $content = $builder->fromRestApi(
                    $package,
                    $input->getOption('api-version') ?: null,
                    $input->getOption('audience') ?: null,
                    $markdownPath,
                );
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($input->getOption('stdout')) {
            $output->writeln($content);

            return Command::SUCCESS;
        }

        $path = $this->resolveOutputPath($package, $input, new ApiDocsGenerator(), (string)($input->getOption('output') ?: ''));

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        $this->success('PinDoc HTML generated: ' . $path);

        return Command::SUCCESS;
    }

    private function resolveMarkdownPath(string $package, string $path): string
    {
        $loader = new PinDocMarkdownLoader();

        if ($package !== '') {
            return $loader->resolvePath($package, $path);
        }

        $path = str_replace('\\', '/', $path);

        if (preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        return $this->resolveProjectDocPath($path);
    }

    private function resolveOutputPath(string $package, InputInterface $input, ApiDocsGenerator $generator, string $explicit): string
    {
        if ($explicit !== '') {
            if ($this->isAbsoluteDocPath($explicit) || str_starts_with(str_replace('\\', '/', $explicit), 'apps/')) {
                return $this->resolveProjectDocPath($explicit);
            }

            return AppEngine::path($package, $explicit);
        }

        return AppEngine::path(
            $package,
            $generator->defaultOutputRelativePath($package, 'html', $input->getOption('audience') ?: null),
        );
    }
}

