<?php

namespace Pinoox\Terminal\Pinx;

use Pinoox\Component\Package\AppDependency;
use Pinoox\Component\Package\Pinx\PinxReader;
use Pinoox\Component\Package\Pinx\PinxSignKey;
use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pinx:info',
    description: 'Show metadata of a .pinx/.pin package',
)]

class PinxInfoCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'Path to .pinx/.pin file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $path = (string) $input->getArgument('package');

        if (!is_file($path)) {
            $io->error('Package file not found: ' . $path);
            return Command::FAILURE;
        }

        try {
            $reader = new PinxReader();
            $reader->open($path);
            $manifest = $reader->manifest();
            $zip = $reader->zip();
            $signature = $reader->signature();
            $fileCount = count($zip->getListFiles());
            $reader->close();

            $rows = [
                ['Format', (string) ($manifest->toArray()['format'] ?? 'pinx')],
                ['Type', $manifest->type()],
                ['Package', $manifest->package()],
                ['Name', $manifest->name()],
                ['Version', $manifest->versionName() . ' #' . $manifest->versionCode()],
                ['Developer', $manifest->developer()],
                ['Min Pinoox', (string) $manifest->minpin()],
                ['Files in archive', (string) $fileCount],
            ];

            if ($manifest->isTheme()) {
                $rows[] = ['Target app', $manifest->targetApp()];
                $rows[] = ['Theme name', $manifest->themeName()];
            }

            $depends = $manifest->depends();
            if ($depends !== []) {
                foreach (AppDependency::inspect($depends, \Pinoox\Portal\App\AppEngine::___()) as $dep) {
                    $label = $dep['package'];
                    if ($dep['optional']) {
                        $label .= ' (optional)';
                    }
                    if ($dep['min_code'] !== null) {
                        $label .= ' >= ' . $dep['min_code'];
                    }
                    $status = $dep['installed']
                        ? ('installed #' . ($dep['version_code'] ?? '?'))
                        : 'missing';
                    $rows[] = ['Depends: ' . $label, $status];
                }
            } else {
                $rows[] = ['Depends', 'none'];
            }

            if ($signature !== null) {
                $rows[] = ['Signed', 'yes'];
                $rows[] = ['Algorithm', (string) ($signature['algorithm'] ?? PinxSignKey::ALGORITHM)];
                $rows[] = ['Key ID', (string) ($signature['key_id'] ?? '')];
                $rows[] = ['Fingerprint', (string) ($signature['fingerprint'] ?? '')];
                $rows[] = ['Signed at', (string) ($signature['signed_at'] ?? '')];
            } else {
                $rows[] = ['Signed', 'no'];
            }

            $io->title('Pinx package info');
            $io->table(['Key', 'Value'], $rows);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}

