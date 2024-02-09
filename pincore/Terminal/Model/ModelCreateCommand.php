<?php

namespace Pinoox\Terminal\Model;

use Pinoox\Component\Helpers\PhpFile\ModelFile;
use Pinoox\Component\Helpers\Str;
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

    protected function configure(): void
    {
        $this
            ->addArgument('model', InputArgument::REQUIRED, 'Enter name of model class')
            ->addArgument('package', InputArgument::REQUIRED, 'Enter the package name of app you want to migrate schemas');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->model = $input->getArgument('model');
        $this->package = $input->getArgument('package');

        $this->init();
        $this->create();

        return Command::SUCCESS;
    }

    private function init(): void
    {
        if (!AppEngine::exists($this->package)) {
            $this->error('Package not found');
        }
    }

    private function create(): void
    {
        try {
            $isCreated = StubGenerator::generate('model.create.stub', $this->getExportPath(), [
                'copyright' => StubGenerator::get('copyright.stub'),
                'package' => $this->package,
                'model' => $this->model.'Model',
                'table' => $this->table,
            ]);

            if ($isCreated) {
                $this->success('✓ Model [' . $this->model . '] created successfully');
                $this->newLine();
            } else {
                $this->error('Can\'t generate a new model!');
            }
        } catch (\Exception $e) {
            $this->error($e);
        }
    }


    private function getExportPath(): string
    {
        $path = AppEngine::path($this->package) . '/Model';

        $this->model = Str::toCamelCase($this->model);
        $this->table = Str::toUnderScore($this->model);

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        } else {
            //check availability
            $finder = new Finder();
            $finder->in($path)
                ->files()
                ->name('*.php');
        }

        return $path . '/' . $this->model . 'Model.php';
    }
}