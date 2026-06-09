<?php

namespace Pinoox\Terminal\Pinx;

use Pinoox\Component\Package\AppDependency;
use Pinoox\Component\Package\Pinx\PinxBuilder;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pinx:build',
    description: 'Build a .pinx install package from an app or theme',
    aliases: ['pinx:b'],
)]

class PinxBuildCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Build a .pinx package using app.php build/pinx settings.

Examples:
  php pinoox pinx:build com_my_shop
  php pinoox pinx:build com_my_shop --sign
  php pinoox pinx:build com_my_shop --output=/tmp/my_shop.pinx
  php pinoox pinx:build com_my_shop --yes
HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, 'App package name')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output .pinx file path')
            ->addOption('sign', 's', InputOption::VALUE_NONE, 'Sign the package (auto when key exists or app.php pinx.sign.enabled)')
            ->addOption('no-sign', null, InputOption::VALUE_NONE, 'Build without signing even when a key exists')
            ->addOption('sign-key', null, InputOption::VALUE_REQUIRED, 'Path to sign.key.json')
            ->addOption('key-id', null, InputOption::VALUE_REQUIRED, 'Publisher key id stored in signature.json')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePackageRequired($input, $output, $io, [
            'excludeSystem' => true,
            'sectionTitle' => 'Packages available for pinx build',
        ]);

        $builder = new PinxBuilder(AppEngine::___());
        $outputPath = $input->getOption('output');

        if (!$input->getOption('yes')) {
            $io->section('Pinx build');
            $io->text([
                'Package: <info>' . $package . '</info>',
                'Output: <info>' . ($outputPath ?: '(auto in export/)') . '</info>',
            ]);

            if (!$io->confirm('Proceed with build?', false)) {
                $io->warning('Build canceled.');
                return Command::SUCCESS;
            }
        }

        $progress = new ProgressBar($output);
        $progress->start();

        $buildOptions = [];
        if ($input->getOption('no-sign')) {
            $buildOptions['sign'] = false;
        } elseif ($input->getOption('sign')) {
            $buildOptions['sign'] = true;
        }
        if ($input->getOption('sign-key')) {
            $buildOptions['sign_key'] = (string) $input->getOption('sign-key');
        }
        if ($input->getOption('key-id')) {
            $buildOptions['key_id'] = (string) $input->getOption('key-id');
        }

        try {
            $result = $builder->build($package, $outputPath, $buildOptions);
            $progress->finish();
            $output->writeln('');

            $manifest = $result['manifest'];
            $io->success('Pinx package created successfully.');
            $rows = [
                ['File', $result['path']],
                ['Type', $manifest->type()],
                ['Package', $manifest->package()],
                ['Version', $manifest->versionName() . ' #' . $manifest->versionCode()],
                ['Files', (string) $result['files']],
                ['Signed', $result['signed'] ? 'yes' : 'no'],
            ];

            if ($result['signed'] && is_array($result['signature'])) {
                $rows[] = ['Key ID', (string) ($result['signature']['key_id'] ?? '')];
                $rows[] = ['Fingerprint', (string) ($result['signature']['fingerprint'] ?? '')];
            }

            $depends = AppDependency::inspect(
                AppDependency::fromAppConfig(PinxBuildConfig::appConfigArray(AppEngine::___(), $package)),
                AppEngine::___(),
            );
            if ($depends !== []) {
                $dependsSummary = implode(', ', array_map(
                    static fn (array $row): string => $row['package']
                        . ($row['optional'] ? ' (optional)' : '')
                        . ($row['min_code'] !== null ? ' >=' . $row['min_code'] : ''),
                    $depends,
                ));
                $rows[] = ['Depends', $dependsSummary];
            }

            if (!empty($result['composer'])) {
                $rows[] = ['Composer', 'vendor/ prepared with --no-dev'];
            }

            $io->definitionList(...array_map(static fn (array $row) => [$row[0] => $row[1]], $rows));

            if ($manifest->isTheme()) {
                $io->note('Theme package for app ' . $manifest->targetApp() . ', theme ' . $manifest->themeName());
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $progress->clear();
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}

