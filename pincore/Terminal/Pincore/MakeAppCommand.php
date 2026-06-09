<?php

namespace Pinoox\Terminal\Pincore;

use Pinoox\Component\Package\Scaffold\AppCreateInput;
use Pinoox\Component\Package\Scaffold\AppCreateScaffolder;
use Pinoox\Component\Template\Frontend\ThemeFrontend;
use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create',
    description: 'Create a new app with an interactive wizard (package, frontend stack, routes)',
    aliases: ['make:app'],
)]

class MakeAppCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Creates a new HMVC app under apps/ with routes, theme, and optional Vite frontend.

Interactive wizard:
  php pinoox app:create

Simple mode — minimal Twig app, few questions, no route:
  php pinoox app:create --simple
  php pinoox app:create com_my_shop --simple

Quick create with options:
  php pinoox app:create com_my_shop --stack=vue --profile=hybrid --route=/shop -y

Stacks:
  none   Twig only — simple HTML page, no Node.js
  twig   Twig layouts — structured server-rendered pages
  vite   Vite only — vanilla JS + Twig shell (no Vue/React)
  vue    Vue + Vite — SPA or hybrid public site
  react  React + Vite — SPA or hybrid public site

Profiles (vite/vue/react only):
  hybrid  Public site — Twig shell + SEO (default)
  spa     Admin panel — client-side routing
HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, 'Package name (e.g. com_my_shop). Leave empty for the wizard.')
            ->addOption('stack', null, InputOption::VALUE_REQUIRED, 'Frontend stack: none, twig, vite, vue, react')
            ->addOption('profile', null, InputOption::VALUE_REQUIRED, 'Frontend profile for vite/vue/react: spa, hybrid')
            ->addOption('display-name', null, InputOption::VALUE_REQUIRED, 'App display name')
            ->addOption('developer', null, InputOption::VALUE_REQUIRED, 'Developer name')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Short description')
            ->addOption('route', null, InputOption::VALUE_REQUIRED, 'Register a URL path for this app (e.g. /my-shop)')
            ->addOption('no-route', null, InputOption::VALUE_NONE, 'Skip router registration')
            ->addOption('install', null, InputOption::VALUE_NONE, 'Run npm install after creating a Vite stack')
            ->addOption('simple', 's', InputOption::VALUE_NONE, 'Simple mode — Twig-only app, auto defaults, minimal prompts')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip the final confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $simple = (bool) $input->getOption('simple');
        $io->title($simple ? 'Pinoox App Creator — simple' : 'Pinoox App Creator');

        try {
            $createInput = $simple
                ? $this->resolveSimpleInput($input, $io)
                : $this->resolveInput($input, $io);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $scaffolder = new AppCreateScaffolder($createInput);

        if ($scaffolder->exists()) {
            $io->error("App '{$createInput->package}' already exists.");

            return Command::FAILURE;
        }

        $skipConfirm = $simple || $input->getOption('yes');

        if (!$skipConfirm) {
            $this->renderSummary($io, $createInput);

            if (!$io->confirm('Create this app?', true)) {
                $io->warning('Cancelled.');

                return Command::SUCCESS;
            }
        } elseif ($simple) {
            $io->text([
                'Package: <info>' . $createInput->package . '</info>',
                'Mode: Twig-only page, tests included, no URL route',
            ]);
        }

        $result = $scaffolder->scaffold();

        $io->success("App '{$result->package}' created in {$result->appDir}");

        if ($result->routePath !== null) {
            $io->note("Route registered: {$result->routePath} → {$result->package}");
        }

        $io->section('Next steps');
        $io->listing($result->nextSteps);

        if ($createInput->hasViteStack() && $input->getOption('install')) {
            $io->section('Installing npm dependencies');
            $frontend = ThemeFrontend::forPackageAndTheme($result->package, 'default');
            $frontend->setOutputWriter(static fn (string $buffer) => $output->write($buffer));
            $code = $frontend->install();

            if ($code !== 0) {
                $io->warning('npm install failed — run: php pinoox fe ' . $result->package . ' install');

                return Command::FAILURE;
            }

            $io->success('Dependencies installed.');
        }

        return Command::SUCCESS;
    }

    private function resolveSimpleInput(InputInterface $input, SymfonyStyle $io): AppCreateInput
    {
        $package = $this->resolvePackage($input, $io, simple: true);

        return AppCreateInput::simple($package);
    }

    private function resolveInput(InputInterface $input, SymfonyStyle $io): AppCreateInput
    {
        if (!$input->getOption('simple') && trim((string) $input->getArgument('package')) === '') {
            $mode = (string) $io->choice(
                'Create mode',
                [
                    'simple' => 'Simple — Twig page, minimal setup (recommended)',
                    'full' => 'Full wizard — stack, profile, route, …',
                ],
                'simple',
            );

            if ($mode === 'simple') {
                $package = $this->resolvePackage($input, $io, simple: true);

                return AppCreateInput::simple($package);
            }
        }

        $package = $this->resolvePackage($input, $io);
        $displayName = $this->resolveDisplayName($input, $package, $io);
        $developer = $this->resolveDeveloper($input, $io);
        $description = $this->resolveDescription($input, $displayName, $io);
        $stack = $this->resolveStack($input, $io);
        $profile = $this->resolveProfile($input, $stack, $io);
        [$registerRoute, $routePath] = $this->resolveRoute($input, $package, $io);

        return new AppCreateInput(
            package: $package,
            displayName: $displayName,
            developer: $developer,
            description: $description,
            stack: $stack,
            profile: $profile,
            registerRoute: $registerRoute,
            routePath: $routePath,
        );
    }

    private function resolvePackage(InputInterface $input, SymfonyStyle $io, bool $simple = false): string
    {
        $raw = trim((string) $input->getArgument('package'));

        if ($raw !== '') {
            $package = AppCreateScaffolder::normalizePackageName($raw);
            $this->assertValidPackage($package);

            return $package;
        }

        if ($simple) {
            $io->text('Simple mode: Twig-only app with tests. Enter a package name.');
        } else {
            $io->section('Step 1 — Package name');
            $io->text([
                'Choose a unique folder name under <info>apps/</info>.',
                'Convention: <info>com_{vendor}_{name}</info> (e.g. com_acme_shop)',
            ]);
        }

        while (true) {
            $answer = trim((string) $io->ask('Package name', 'com_my_app'));
            $package = AppCreateScaffolder::normalizePackageName($answer);

            if (!AppCreateScaffolder::isValidPackageName($package)) {
                $io->warning('Use lowercase letters, numbers, and underscores only.');

                continue;
            }

            if ((new AppCreateScaffolder(new AppCreateInput($package, 'x', '', '')))->exists()) {
                $io->warning("App '{$package}' already exists.");

                continue;
            }

            return $package;
        }
    }

    private function resolveDisplayName(InputInterface $input, string $package, SymfonyStyle $io): string
    {
        $option = trim((string) $input->getOption('display-name'));
        if ($option !== '') {
            return $option;
        }

        if (trim((string) $input->getArgument('package')) === '') {
            $io->section('Step 2 — App details');
        }

        $default = AppCreateScaffolder::displayNameFromPackage($package);

        return trim((string) $io->ask('Display name', $default));
    }

    private function resolveDeveloper(InputInterface $input, SymfonyStyle $io): string
    {
        $option = trim((string) $input->getOption('developer'));
        if ($option !== '') {
            return $option;
        }

        if (trim((string) $input->getArgument('package')) === '' && trim((string) $input->getOption('display-name')) === '') {
            // Step header already shown in resolveDisplayName
        }

        return trim((string) $io->ask('Developer', 'pinoox developer'));
    }

    private function resolveDescription(InputInterface $input, string $displayName, SymfonyStyle $io): string
    {
        $option = trim((string) $input->getOption('description'));
        if ($option !== '') {
            return $option;
        }

        return trim((string) $io->ask('Description', $displayName));
    }

    private function resolveStack(InputInterface $input, SymfonyStyle $io): string
    {
        $option = strtolower(trim((string) $input->getOption('stack')));
        if ($option !== '') {
            if (!in_array($option, AppCreateInput::STACKS, true)) {
                throw new \InvalidArgumentException('Invalid --stack. Use: none, twig, vite, vue, react.');
            }

            return $option;
        }

        if (trim((string) $input->getArgument('package')) === '') {
            $io->section('Step 3 — Frontend stack');
        }

        return (string) $io->choice(
            'Which frontend do you want?',
            [
                AppCreateInput::STACK_NONE => 'None — simple Twig HTML (no Node.js)',
                AppCreateInput::STACK_TWIG => 'Twig layouts — server-rendered pages with structure',
                AppCreateInput::STACK_VITE => 'Vite only — vanilla JavaScript (no Vue/React)',
                AppCreateInput::STACK_VUE => 'Vue + Vite — interactive UI',
                AppCreateInput::STACK_REACT => 'React + Vite — interactive UI',
            ],
            AppCreateInput::STACK_NONE,
        );
    }

    private function resolveProfile(InputInterface $input, string $stack, SymfonyStyle $io): string
    {
        if (!in_array($stack, [AppCreateInput::STACK_VITE, AppCreateInput::STACK_VUE, AppCreateInput::STACK_REACT], true)) {
            return AppCreateInput::PROFILE_HYBRID;
        }

        $option = strtolower(trim((string) $input->getOption('profile')));
        if ($option !== '') {
            if (!in_array($option, [AppCreateInput::PROFILE_SPA, AppCreateInput::PROFILE_HYBRID], true)) {
                throw new \InvalidArgumentException('Invalid --profile. Use: spa, hybrid.');
            }

            return $option;
        }

        if (trim((string) $input->getArgument('package')) === '' && trim((string) $input->getOption('stack')) === '') {
            $io->section('Step 4 — Frontend profile');
        }

        return (string) $io->choice(
            'How should ' . $stack . ' render pages?',
            [
                AppCreateInput::PROFILE_HYBRID => 'Hybrid — public site with Twig shell and SEO',
                AppCreateInput::PROFILE_SPA => 'SPA — admin panel with client-side routing',
            ],
            AppCreateInput::PROFILE_HYBRID,
        );
    }

    /**
     * @return array{0: bool, 1: string|null}
     */
    private function resolveRoute(InputInterface $input, string $package, SymfonyStyle $io): array
    {
        if ($input->getOption('no-route')) {
            return [false, null];
        }

        $routeOption = trim((string) $input->getOption('route'));
        if ($routeOption !== '') {
            return [true, '/' . ltrim($routeOption, '/')];
        }

        if (trim((string) $input->getArgument('package')) === '') {
            $io->section('Step 5 — URL route');
        }

        $defaultPath = '/' . $package;

        if (!$io->confirm('Register a URL path for this app?', true)) {
            return [false, null];
        }

        $routePath = trim((string) $io->ask('Route path', $defaultPath));

        return [true, '/' . ltrim($routePath, '/')];
    }

    private function renderSummary(SymfonyStyle $io, AppCreateInput $createInput): void
    {
        $io->section('Summary');

        $rows = [
            ['Package', $createInput->package],
            ['Display name', $createInput->displayName],
            ['Developer', $createInput->developer],
            ['Description', $createInput->description],
            ['Frontend', $this->stackLabel($createInput->stack)],
        ];

        if ($createInput->hasViteStack()) {
            $rows[] = ['Profile', $createInput->profile];
        }

        $rows[] = ['Route', $createInput->registerRoute
            ? ($createInput->routePath ?? '—')
            : 'not registered'];

        $io->table(['Setting', 'Value'], $rows);
    }

    private function stackLabel(string $stack): string
    {
        return match ($stack) {
            AppCreateInput::STACK_NONE => 'none (Twig only)',
            AppCreateInput::STACK_TWIG => 'twig layouts',
            AppCreateInput::STACK_VITE => 'vite only',
            AppCreateInput::STACK_VUE => 'vue + vite',
            AppCreateInput::STACK_REACT => 'react + vite',
            default => $stack,
        };
    }

    private function assertValidPackage(string $package): void
    {
        if (!AppCreateScaffolder::isValidPackageName($package)) {
            throw new \InvalidArgumentException(
                "Invalid package name '{$package}'. Use lowercase letters, numbers, and underscores.",
            );
        }
    }
}
