<?php

namespace Pinoox\Terminal\Pinx;

use Pinoox\Component\Package\Pinx\PinxSignKey;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\SystemConfig;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pinx:sign-keygen',
    description: 'Generate an Ed25519 signing key pair for pinx package builds',
)]

class PinxSignKeygenCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Creates a signing key for pinx:build. Keys are stored locally and never included in .pinx files.

Default locations:
  apps/{package}/pinx/sign.key.json
  storage/pinx/keys/{package}.key.json

Examples:
  php pinoox pinx:sign-keygen com_my_shop
  php pinoox pinx:sign-keygen com_my_shop --global
HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, 'App package name')
            ->addOption('global', 'g', InputOption::VALUE_NONE, 'Store key in storage/pinx/keys instead of app folder')
            ->addOption('key-id', null, InputOption::VALUE_REQUIRED, 'Publisher key id (default: {package}:main)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing key file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePackageRequired($input, $output, $io, [
            'excludeSystem' => true,
            'sectionTitle' => 'Packages available for key generation',
        ]);

        $engine = AppEngine::___();
        if (!$engine->exists($package)) {
            $io->error('Package not found: ' . $package);
            return Command::FAILURE;
        }

        $appPath = $engine->path($package);
        $path = (bool) $input->getOption('global')
            ? rtrim(SystemConfig::resolvePath('~storage/pinx/keys'), '/\\') . '/' . $package . '.key.json'
            : rtrim($appPath, '/\\') . '/pinx/' . PinxSignKey::KEY_FILE;

        if (is_file($path) && !(bool) $input->getOption('force')) {
            $io->error('Key already exists: ' . $path . ' (use --force to overwrite)');
            return Command::FAILURE;
        }

        $keyId = (string) ($input->getOption('key-id') ?: $package . ':main');
        $key = PinxSignKey::generate($package, $keyId);
        PinxSignKey::save($key, $path);

        $io->success('Signing key generated.');
        $io->definitionList(
            ['Package' => $package],
            ['Key ID' => $key['key_id']],
            ['Fingerprint' => PinxSignKey::fingerprint($key['public_key'])],
            ['Path' => $path],
        );
        $io->warning('Keep the secret key private. Never commit sign.key.json to public repositories.');

        return Command::SUCCESS;
    }
}

