<?php

namespace Pinoox\Terminal\Request;

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
    name: 'form-request:create',
    description: 'Create a new FormRequest class in an app',
)]

class FormRequestCreateCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Creates an ApiFormRequest stub inside apps/{package}/Request/.

Example:

  php pinoox form-request:create StoreProductRequest com_my_shop

HELP
            )
            ->addArgument('request', InputArgument::REQUIRED, 'FormRequest class name (e.g. StoreProductRequest)')
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $request = $input->getArgument('request');
        $package = $this->resolvePackageRequired($input, $output, $io, [
            'sectionTitle' => 'Create form request in',
        ]);

        $stub = new StubBuilderHelper($request, $package, 'Request');
        $isCreated = $stub->generate('form-request.create.stub');

        if ($isCreated) {
            $this->success($stub->message);
            $this->newLine();
        } else {
            $this->error($stub->message);
        }

        return Command::SUCCESS;
    }
}

