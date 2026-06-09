<?php

namespace Pinoox\Terminal\Theme;

use Pinoox\Component\Template\Frontend\ThemeFrontend;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Pinoox\Terminal\Concerns\SelectsTheme;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'theme:frontend',
    description: 'Build, run, inspect, or scaffold frontend assets for an app theme',
    aliases: ['fe', 'frontend'],
)]

class ThemeFrontendCommand extends Terminal
{
    use SelectsPackage;
    use SelectsTheme;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Manage frontend assets inside apps/{package}/theme/{theme}/.

Actions:
  info      Show detected stack, manifest, npm scripts, and dev-server status
  install   Install npm dependencies (skips when up to date; use --install to force)
  build     Run npm run build
  dev       Run npm run dev (Vite HMR, live output)
  run       Run any npm script from package.json (--script=name)
  scaffold  Copy starter files for vue, react, or twig-only themes

Examples:
  php pinoox fe info
  php pinoox fe dev spark
  php pinoox fe build com_my_shop
  php pinoox fe dev com_my_shop --theme=admin
  php pinoox fe dev --theme=spark
  php pinoox fe run com_my_shop --script=preview
  php pinoox fe install com_my_shop --install
  php pinoox fe scaffold com_my_shop --stack=vue

The second argument accepts an app package (com_my_shop) or a theme folder name (spark).
If the theme name exists in one app only, the package is resolved automatically.
If it exists in multiple apps, pick the package from a list.

Package and theme can also be omitted — pick from a list interactively.

build, dev, and run skip npm install by default (faster workflow).
Use --install to install dependencies alongside the command when needed.
The install action runs npm install; add --install to force reinstall.

Development (.env):
  VITE_DEV=true
  VITE_DEV_SERVER=http://127.0.0.1:5173

When Vite dev is running, write the dev-server URL to theme/dist/hot for automatic HMR tags.
HELP
            )
            ->addArgument('action', InputArgument::REQUIRED, 'Action: info, install, build, dev, run, scaffold')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package (com_my_shop) or theme folder name (spark). Leave empty to pick interactively.')
            ->addOption('stack', null, InputOption::VALUE_REQUIRED, 'Frontend stack for scaffold: vue, react, twig')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Theme folder name (defaults to app.php theme or interactive pick)')
            ->addOption('script', null, InputOption::VALUE_REQUIRED, 'npm script name for the run action')
            ->addOption('install', null, InputOption::VALUE_NONE, 'Run npm install alongside the command (or force reinstall with the install action)')
            ->addOption('no-install', null, InputOption::VALUE_NONE, 'Skip npm install (default for build/dev/run)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $action = strtolower(trim((string) $input->getArgument('action')));

        if (!in_array($action, ['info', 'install', 'build', 'dev', 'run', 'scaffold'], true)) {
            $io->error('Unknown action "' . $action . '". Use info, install, build, dev, run, or scaffold.');

            return Command::FAILURE;
        }

        try {
            $target = $this->resolveTarget($input, $output, $io, $action);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $package = $target['package'];
        $themeName = $target['theme'];

        $frontend = ThemeFrontend::forPackageAndTheme($package, $themeName);
        $frontend->setOutputWriter(static fn (string $buffer) => $output->write($buffer));

        $installMode = $this->resolveInstallMode($input, $action);

        try {
            return match ($action) {
                'info' => $this->runInfo($io, $frontend),
                'install' => $this->runInstall($io, $frontend, $installMode),
                'build' => $this->runBuild($io, $frontend, $installMode),
                'dev' => $this->runDev($io, $frontend, $installMode),
                'run' => $this->runScript($input, $output, $io, $frontend, $installMode),
                'scaffold' => $this->runScaffold($io, $frontend, (string) $input->getOption('stack')),
            };
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @return array{package: string, theme: string}
     */
    private function resolveTarget(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        string $action,
    ): array {
        $positional = $this->readPackageInput($input, 'package', ['package', 'app']);
        $themeOption = $this->readThemeInput($input);

        if ($positional !== '' && AppEngine::exists($positional)) {
            return [
                'package' => $positional,
                'theme' => $this->resolveThemeForPackage($input, $output, $io, $positional, $action, $themeOption),
            ];
        }

        $themeName = $themeOption !== '' ? $themeOption : $positional;
        if ($themeName !== '') {
            return $this->resolveByThemeName($input, $output, $io, $themeName);
        }

        $candidates = $this->frontendPackageCandidates();

        $package = $candidates !== []
            ? $this->resolvePackageFromCandidates($input, $output, $io, $candidates, [
                'sectionTitle' => 'Apps with frontend themes',
                'emptyMessage' => 'No apps with theme folders were found.',
            ])
            : $this->resolvePackageRequired($input, $output, $io, [
                'sectionTitle' => 'Theme frontend for',
                'appsOnly' => true,
            ]);

        return [
            'package' => $package,
            'theme' => $this->resolveThemeForPackage($input, $output, $io, $package, $action, ''),
        ];
    }

    /**
     * @return array{package: string, theme: string}
     */
    private function resolveByThemeName(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        string $themeName,
    ): array {
        $packages = ThemeFrontend::findPackagesByThemeFolder($themeName);

        if ($packages === []) {
            throw new \RuntimeException(sprintf("Theme '%s' was not found in any app.", $themeName));
        }

        if (count($packages) === 1) {
            $package = array_key_first($packages);
            $io->note(sprintf('Using theme %s in %s', $themeName, $package));

            return ['package' => $package, 'theme' => $themeName];
        }

        $package = $this->resolvePackageFromCandidates($input, $output, $io, $packages, [
            'sectionTitle' => sprintf("Apps with theme '%s'", $themeName),
            'emptyMessage' => sprintf("Theme '%s' was not found in any app.", $themeName),
            'invalidMessage' => "Package '%s' does not contain theme '$themeName'.",
        ]);

        return ['package' => $package, 'theme' => $themeName];
    }

    private function resolveThemeForPackage(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        string $package,
        string $action,
        string $themeOption,
    ): string {
        $themeChoices = ThemeFrontend::listThemeFolders($package);
        $defaultTheme = (string) AppEngine::config($package)->get('theme', 'default');

        if ($themeOption !== '') {
            if (!isset($themeChoices[$themeOption]) && $action !== 'scaffold') {
                throw new \RuntimeException(sprintf("Theme '%s' was not found in package '%s'.", $themeOption, $package));
            }

            return $themeOption;
        }

        if ($themeChoices === [] && $action !== 'scaffold') {
            throw new \RuntimeException('No theme folders were found under apps/' . $package . '/theme/.');
        }

        if ($action === 'scaffold' && $themeChoices === []) {
            return $defaultTheme;
        }

        return $this->resolveThemeChoice($input, $output, $io, $package, $themeChoices, [
            'default' => $defaultTheme,
            'sectionTitle' => 'Themes in ' . $package,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function frontendPackageCandidates(): array
    {
        $candidates = [];

        foreach (AppEngine::all() as $package => $manager) {
            if (ThemeFrontend::listThemeFolders($package) === []) {
                continue;
            }

            $candidates[$package] = (string) ($manager->config()->get('name') ?: $package);
        }

        return $candidates;
    }

    private function resolveInstallMode(InputInterface $input, string $action): string
    {
        if ((bool) $input->getOption('no-install')) {
            return ThemeFrontend::INSTALL_SKIP;
        }

        if ((bool) $input->getOption('install')) {
            return $action === 'install'
                ? ThemeFrontend::INSTALL_FORCE
                : ThemeFrontend::INSTALL_SMART;
        }

        if ($action === 'install') {
            return ThemeFrontend::INSTALL_SMART;
        }

        return ThemeFrontend::INSTALL_SKIP;
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
            ['node_modules' => $info['node_modules'] ? 'yes' : 'no'],
            ['Needs npm install' => $info['needs_npm_install'] ? 'yes' : 'no'],
            ['Dev enabled' => $info['dev_enabled'] ? 'yes' : 'no'],
            ['Dev URL' => (string) ($info['dev_url'] ?? '-')],
        );

        $scripts = $info['npm_scripts'];
        if ($scripts !== []) {
            $io->section('npm scripts');
            $rows = [];
            foreach ($scripts as $name => $command) {
                $rows[] = [$name, $command];
            }
            $io->table(['Script', 'Command'], $rows);
        }

        return Command::SUCCESS;
    }

    private function runInstall(SymfonyStyle $io, ThemeFrontend $frontend, string $installMode): int
    {
        $io->section('npm install: ' . $frontend->themePath());

        if ($installMode === ThemeFrontend::INSTALL_SKIP) {
            $io->warning('Skipped (--no-install).');

            return Command::SUCCESS;
        }

        if ($installMode === ThemeFrontend::INSTALL_SMART && !$frontend->needsNpmInstall()) {
            $io->success('Dependencies are already up to date. Use --install to force reinstall.');

            return Command::SUCCESS;
        }

        $code = $frontend->install();

        return $code === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function runBuild(SymfonyStyle $io, ThemeFrontend $frontend, string $installMode): int
    {
        $io->section('Building frontend: ' . $frontend->themePath());
        $this->noteInstallPlan($io, $frontend, $installMode);

        $code = $frontend->build($installMode);

        return $code === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function runDev(SymfonyStyle $io, ThemeFrontend $frontend, string $installMode): int
    {
        $io->section('Starting frontend dev server: ' . $frontend->themePath());
        $this->noteInstallPlan($io, $frontend, $installMode);
        $io->note([
            'Live output streams below. Press Ctrl+C to stop.',
            'Set VITE_DEV=true in .env or create theme/dist/hot with the Vite URL.',
            'Proxy API calls from vite.config.js to your Pinoox URL (see docs/pinoox-frontend.md).',
        ]);

        return $frontend->dev($installMode) === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function runScript(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        ThemeFrontend $frontend,
        string $installMode,
    ): int {
        $script = trim((string) $input->getOption('script'));
        $scripts = $frontend->npmScripts();

        if ($script === '') {
            if ($scripts === []) {
                $io->error('No npm scripts were found in package.json.');

                return Command::FAILURE;
            }

            if (count($scripts) === 1) {
                $script = array_key_first($scripts);
                $io->note('Using the only npm script: ' . $script);
            } elseif ($input->isInteractive()) {
                $io->section('npm scripts');
                $rows = [];
                foreach ($scripts as $name => $command) {
                    $rows[] = [count($rows), $name, $command];
                }
                $io->table(['#', 'Script', 'Command'], $rows);

                $question = new Question('Select script: ');
                $question->setAutocompleterValues(array_keys($scripts));
                $question->setValidator(function ($answer) use ($scripts) {
                    $answer = trim((string) $answer);
                    if (isset($scripts[$answer])) {
                        return $answer;
                    }
                    if (ctype_digit($answer)) {
                        $keys = array_keys($scripts);

                        return $keys[(int) $answer] ?? throw new \RuntimeException("Script '$answer' was not found.");
                    }

                    throw new \RuntimeException("Script '$answer' was not found.");
                });
                $script = $this->getHelper('question')->ask($input, $output, $question);
            } else {
                $io->error('Script is required in non-interactive mode. Use --script=name.');

                return Command::FAILURE;
            }
        }

        $io->section('Running npm run ' . $script . ': ' . $frontend->themePath());
        $this->noteInstallPlan($io, $frontend, $installMode);

        $code = $frontend->runScript($script, $installMode);

        return $code === 0 ? Command::SUCCESS : Command::FAILURE;
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
            $theme = basename($frontend->themePath());
            $io->writeln('Next: php pinoox fe install ' . $theme);
            $io->writeln('Then: php pinoox fe dev ' . $theme);
        }

        return Command::SUCCESS;
    }

    private function noteInstallPlan(SymfonyStyle $io, ThemeFrontend $frontend, string $installMode): void
    {
        if ($installMode === ThemeFrontend::INSTALL_SKIP) {
            return;
        }

        if ($installMode === ThemeFrontend::INSTALL_FORCE) {
            $io->writeln('<comment>npm install: forced (--install)</comment>');

            return;
        }

        if ($frontend->needsNpmInstall()) {
            $io->writeln('<comment>npm install: dependencies changed or missing — installing…</comment>');

            return;
        }

        $io->writeln('<info>npm install: skipped (dependencies up to date)</info>');
    }
}
