<?php

namespace Pinoox\Terminal\Controller;

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Helpers\StubBuilderHelper;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\StubGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'controller:create',
    description: 'Create a new controller class.',
)]
class ControllerCreateCommand extends Terminal
{
    private string $package;
    private string $controller;
    private string $classname;
    private string $sub;

    protected function configure(): void
    {
        $this
            ->addArgument('controller', InputArgument::REQUIRED, 'Enter name of controller class')
            ->addArgument('package', InputArgument::OPTIONAL, 'Enter the package name of app you want to migrate schemas', $this->getDefaultPackage());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $controller = $input->getArgument('controller');
        $package = $input->getArgument('package');

        if (!AppEngine::exists($package)) {
            $this->error('Package not found');
        }

        $stub = new StubBuilderHelper($controller, $package, 'Controller');
        $isCreated =  $stub->generate('controller.create.stub');

        if ($isCreated) {
            $this->success($stub->message);
            $this->newLine();
        } else {
            $this->error($stub->message);
        }

        return Command::SUCCESS;
    }

}