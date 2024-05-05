<?php

namespace Pinoox\Terminal\Model;

use Pinoox\Component\Helpers\PhpFile\ModelFile;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Helpers\StubBuilderHelper;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\AppManager;
use Pinoox\Portal\StubGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'model:create',
    description: 'Create a new model class.',
)]
class ModelCreateCommand extends Terminal
{
    private string $package;
    private string $model;
    private string $table;
    private string $classname;
    private string $sub;

    protected function configure(): void
    {
        $this
            ->addArgument('model', InputArgument::REQUIRED, 'Enter name of model class')
            ->addArgument('package', InputArgument::OPTIONAL, 'Enter the package name of app you want to migrate schemas', $this->getDefaultPackage());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $model = $input->getArgument('model');
        $package = $input->getArgument('package');
        $table = Str::toUnderScore($model);
        $table = str_replace(['\\','\\_'],'',$table);

        if (!AppEngine::exists($package)) {
            $this->error('Package not found');
        }

        $stub = new StubBuilderHelper($model, $package, 'model');
        $isCreated =  $stub->generate('model.create.stub', [
            'table' => $table,
        ]);

        if ($isCreated) {
            $this->success($stub->message);
            $this->newLine();
        } else {
            $this->error($stub->message);
        }

        return Command::SUCCESS;
    }
}