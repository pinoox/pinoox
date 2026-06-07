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
    name: 'portal:update',
    description: 'Update an existing Portal class from its service',
)]

class UpdatePortalCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Regenerates Portal method stubs from the linked service class.

Example:

  php pinoox portal:update ShopService -p com_my_shop

HELP
            )
            ->addArgument('portalName', InputArgument::REQUIRED, 'Portal class name to update')
            ->addOption('package', 'p', InputOption::VALUE_OPTIONAL, $this->packageArgumentHelp());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('package')) {
            $package = $this->resolvePackageRequired($input, $output, $io, [
                'options' => ['package'],
                'sectionTitle' => 'Update portal in',
            ]);
            $this->bindPackageOption($input, $package);
        }

        $manager = new PortalFile($input, false);
        if ($manager->update()) {
            $this->success(sprintf('Portal updated in "%s".', $manager->getPortalPath()));
            $this->newLine();

            return Command::SUCCESS;
        }

        $this->error(sprintf('Could not update portal in "%s".', $manager->getPortalPath()));

        return Command::INVALID;
    }
}

