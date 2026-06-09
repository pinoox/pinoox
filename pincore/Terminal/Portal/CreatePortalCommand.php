<?php

namespace Pinoox\Terminal\Portal;

use Pinoox\Component\Helpers\PhpFile\PortalFile;
use Pinoox\Component\Terminal;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'portal:create',
    description: 'Create a Portal facade class for an app or platform',
    aliases: ['make:portal'],
)]

class CreatePortalCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Creates a Portal stub that wraps a service class for static access.

Examples:

  php pinoox portal:create ShopService -p com_my_shop

  php pinoox portal:create View

HELP
            )
            ->addArgument('portalName', InputArgument::REQUIRED, 'Portal class name (e.g. ShopService or Admin/ShopService)')
            ->addOption('package', 'p', InputOption::VALUE_OPTIONAL, $this->packageArgumentHelp())
            ->addOption('service', 's', InputOption::VALUE_OPTIONAL, 'Service class name (defaults to portal name)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('package')) {
            $package = $this->resolvePackageRequired($input, $output, $io, [
                'options' => ['package'],
                'sectionTitle' => 'Create portal in',
            ]);
            $this->bindPackageOption($input, $package);
        }

        $manager = new PortalFile($input);
        if ($manager->create()) {
            $this->success(sprintf('Portal created in "%s"', $manager->getPortalPath()));
            $this->newLine();

            return Command::SUCCESS;
        }

        $this->error(sprintf('Same file exists in "%s"!', $manager->getPortalPath()));

        return Command::INVALID;
    }
}

