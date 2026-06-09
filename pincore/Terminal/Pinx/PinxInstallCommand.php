<?php

namespace Pinoox\Terminal\Pinx;

use Pinoox\Component\Package\AppDependency;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Package\Pinx\PinxInstaller;
use Pinoox\Component\Package\Pinx\PinxReader;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\SystemConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pinx:install',
    description: 'Install or update a .pinx/.pin package',
    aliases: ['pinx:i'],
)]

class PinxInstallCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Install or update an app/theme package with the full pipeline:
validate, minpin, extract, migrate, patch, registry, cache.

Examples:
  php pinoox pinx:install packages/com_my_shop_v2.pinx
  php pinoox pinx:install com_my_shop.pinx --force
  php pinoox pinx:install theme_spark.pinx --skip-migrate
  php pinoox pinx:install com_my_shop.pinx --require-sign
HELP
            )
            ->addArgument('package', InputArgument::REQUIRED, 'Path or filename of .pinx/.pin package')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force install even when version is equal or lower')
            ->addOption('skip-migrate', null, InputOption::VALUE_NONE, 'Skip database migrations')
            ->addOption('skip-patch', null, InputOption::VALUE_NONE, 'Skip data patches')
            ->addOption('skip-cache', null, InputOption::VALUE_NONE, 'Skip cache rebuild')
            ->addOption('skip-verify', null, InputOption::VALUE_NONE, 'Skip Ed25519 signature verification')
            ->addOption('require-sign', null, InputOption::VALUE_NONE, 'Reject unsigned packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $packageArg = (string) $input->getArgument('package');
        $packagePath = $this->resolvePackagePath($packageArg);

        if (!is_file($packagePath)) {
            $io->error('Package file not found: ' . $packagePath);
            return Command::FAILURE;
        }

        try {
            $reader = new PinxReader();
            $reader->open($packagePath);
            $manifest = $reader->manifest();
            $reader->close();
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $io->section('Pinx install');
        $io->definitionList(
            ['File' => $packagePath],
            ['Type' => $manifest->type()],
            ['Package' => $manifest->package()],
            ['Version' => $manifest->versionName() . ' #' . $manifest->versionCode()],
            ['Min Pinoox' => (string) $manifest->minpin()],
        );

        if ($manifest->isTheme()) {
            $io->definitionList(
                ['Target app' => $manifest->targetApp()],
                ['Theme' => $manifest->themeName()],
            );
        }

        $depends = $manifest->depends();
        if ($depends !== []) {
            $io->text('Dependencies:');
            foreach (AppDependency::inspect($depends, AppEngine::___()) as $dep) {
                $label = $dep['package'];
                if ($dep['optional']) {
                    $label .= ' (optional)';
                }
                if ($dep['min_code'] !== null) {
                    $label .= ' >= ' . $dep['min_code'];
                }
                $io->writeln(sprintf(
                    '  - %s: %s',
                    $label,
                    $dep['installed'] ? 'installed (code ' . ($dep['version_code'] ?? '?') . ')' : 'missing',
                ));
            }
        }

        if (!$input->getOption('force') && !$io->confirm('Proceed with installation?', true)) {
            $io->warning('Installation canceled.');
            return Command::SUCCESS;
        }

        $installer = new PinxInstaller(
            AppEngine::___(),
            SystemConfig::path('wizard_tmp'),
        );

        $installer->onStep(static function (string $step, string $status, string $message) use ($io): void {
            $io->writeln(sprintf('  <comment>[%s]</comment> %s: %s', strtoupper($status), $step, $message));
        });

        $result = $installer->install($packagePath, [
            'force' => (bool) $input->getOption('force'),
            'skip_migrate' => (bool) $input->getOption('skip-migrate'),
            'skip_patch' => (bool) $input->getOption('skip-patch'),
            'skip_cache' => (bool) $input->getOption('skip-cache'),
            'skip_verify' => (bool) $input->getOption('skip-verify'),
            'require_signature' => (bool) $input->getOption('require-sign'),
        ]);

        if (!$result->success) {
            $io->error($result->message);
            return Command::FAILURE;
        }

        $io->success($result->message);

        return Command::SUCCESS;
    }

    private function resolvePackagePath(string $packageArg): string
    {
        $packageArg = str_replace(['.pinx', '.pin'], '', $packageArg);

        if (is_file($packageArg . '.pinx')) {
            return $packageArg . '.pinx';
        }

        if (is_file($packageArg . '.pin')) {
            return $packageArg . '.pin';
        }

        if (is_file($packageArg)) {
            return $packageArg;
        }

        $candidates = [
            Loader::getBasePath() . '/pins/' . basename($packageArg) . '.pinx',
            Loader::getBasePath() . '/pins/' . basename($packageArg) . '.pin',
            Loader::getBasePath() . '/' . ltrim($packageArg, '/'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $packageArg . '.pinx';
    }
}

