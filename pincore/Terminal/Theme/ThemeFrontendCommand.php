<?php

namespace Pinoox\Terminal\Theme;

use Pinoox\Component\Template\Frontend\ThemeFrontend;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'theme:frontend',
    description: 'Build, run, inspect, or scaffold frontend assets for an app theme',
)]

class ThemeFrontendCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Manage frontend assets inside apps/{package}/theme/{theme}/.

Actions:
  info      Show detected stack, manifest, and dev-server status
  build     Run npm run build in the active theme
  dev       Run npm run dev (Vite HMR)
  scaffold  Copy starter files for vue, react, or twig-only themes

Examples:
  php pinoox theme:frontend info com_my_shop
  php pinoox theme:frontend build com_my_shop
  php pinoox theme:frontend dev com_my_shop
  php pinoox theme:frontend scaffold com_my_shop --stack=vue

Development (.env):
  VITE_DEV=true
  VITE_DEV_SERVER=http://127.0.0.1:5173

When Vite dev is running, write the dev-server URL to theme/dist/hot for automatic HMR tags.
HELP
            )
            ->addArgument('action', InputArgument::REQUIRED, 'Action: info, build, dev, scaffold')
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp())
            ->addOption('stack', null, InputOption::VALUE_REQUIRED, 'Frontend stack for scaffold: vue, react, twig')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Theme folder name (defaults to app.php theme)')
            ->addOption('no-install', null, InputOption::VALUE_NONE, 'Skip npm install before build/dev');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $action = strtolower(trim((string) $input->getArgument('action')));

        if (!in_array($action, ['info', 'build', 'dev', 'scaffold'], true)) {
            $io->error('Unknown action "' . $action . '". Use info, build, dev, or scaffold.');

            return Command::FAILURE;
        }

        $package = $this->resolvePackageRequired($input, $output, $io, [
            'sectionTitle' => 'Theme frontend for',
        ]);

        if (!AppEngine::exists($package)) {
            $io->error('App package not found: ' . $package);

            return Command::FAILURE;
        }

        $themeName = (string) ($input->getOption('theme') ?: AppEngine::config($package)->get('theme', 'default'));
        $themePath = rtrim(str_replace('\\', '/', AppEngine::path($package) . '/theme/' . $themeName), '/');
        $frontend = ThemeFrontend::forPackage($package);

        if ($input->getOption('theme')) {
            $frontend = new \Pinoox\Component\Template\Frontend\ThemeFrontend(
                $package,
                $themePath,
                \Pinoox\Component\Template\Frontend\FrontendConfig::forThemePath($themePath),
            );
        }

        $install = !(bool) $input->getOption('no-install');

        try {
            return match ($action) {
                'info' => $this->runInfo($io, $frontend),
                'build' => $this->runBuild($io, $frontend, $install),
                'dev' => $this->runDev($io, $frontend, $install),
                'scaffold' => $this->runScaffold($io, $frontend, (string) $input->getOption('stack')),
            };
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function runInfo(SymfonyStyle $io, ThemeFrontend $frontend): int
    {
        $info = $frontend->info();
        $io->title('Theme Frontend');
        $io->definitionList(
            ['Package' => $info['package']],
            ['Theme path' => $info['theme_path']],
            ['Stack' => $info['stack']],
            ['Entry' => (string) ($info['entry'] ?? '-')],
            ['Manifest' => $info['manifest']],
            ['Manifest exists' => $info['manifest_exists'] ? 'yes' : 'no'],
            ['package.json' => $info['package_json'] ? 'yes' : 'no'],
            ['Dev enabled' => $info['dev_enabled'] ? 'yes' : 'no'],
            ['Dev URL' => (string) ($info['dev_url'] ?? '-')],
        );

        return Command::SUCCESS;
    }

    private function runBuild(SymfonyStyle $io, ThemeFrontend $frontend, bool $install): int
    {
        $io->section('Building frontend: ' . $frontend->themePath());
        $code = $frontend->build($install);

        return $code === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function runDev(SymfonyStyle $io, ThemeFrontend $frontend, bool $install): int
    {
        $io->section('Starting frontend dev server: ' . $frontend->themePath());
        $io->note([
            'Set VITE_DEV=true in .env or create theme/dist/hot with the Vite URL.',
            'Proxy API calls from vite.config.js to your Pinoox URL (see docs/pinoox-templates.md).',
        ]);

        return $frontend->dev($install) === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function runScaffold(SymfonyStyle $io, ThemeFrontend $frontend, string $stack): int
    {
        $stack = strtolower(trim($stack));
        if ($stack === '') {
            $io->error('Option --stack is required for scaffold (vue, react, twig).');

            return Command::FAILURE;
        }

        $frontend->scaffold($stack);
        $io->success('Scaffolded ' . $stack . ' frontend into ' . $frontend->themePath());

        if (in_array($stack, ['vue', 'react', 'vite'], true)) {
            $io->writeln('Next: cd ' . $frontend->themePath() . ' && npm install && npm run dev');
        }

        return Command::SUCCESS;
    }
}

