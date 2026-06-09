<?php

namespace Pinoox\Terminal\Controller;

use Pinoox\Component\Helpers\StubBuilderHelper;
use Pinoox\Component\Terminal;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'controller:create',
    description: 'Create a new controller class in an app',
    aliases: ['make:controller'],
)]

class ControllerCreateCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Creates a controller stub inside apps/{package}/Controller/.

Example:

  php pinoox controller:create ProductController com_my_shop

HELP
            )
            ->addArgument('controller', InputArgument::REQUIRED, 'Controller class name (e.g. ProductController)')
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $controller = $input->getArgument('controller');
        $package = $this->resolvePackageRequired($input, $output, $io, [
            'sectionTitle' => 'Create controller in',
        ]);

        $stub = new StubBuilderHelper($controller, $package, 'Controller');
        $isCreated = $stub->generate('controller.create.stub');

        if ($isCreated) {
            $this->success($stub->message);
            $this->newLine();
        } else {
            $this->error($stub->message);
        }

        return Command::SUCCESS;
    }
}

