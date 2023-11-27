<?php

namespace pinoox\terminal\model;

use pinoox\component\Helpers\PhpFile\ModelFile;
use pinoox\component\Helpers\Str;
use pinoox\component\Terminal;
use pinoox\portal\AppManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'model:create',
    description: 'Create a new model class.',
)]
class ModelCreateCommand extends Terminal
{
    private string $package;

    private array $app;

    private string $className;

    private string $modelFolder;

    private string $modelFileName;

    protected function configure(): void
    {
        $this
            ->addArgument('model', InputArgument::REQUIRED, 'Enter name of model class')
            ->addArgument('package', InputArgument::REQUIRED, 'Enter the package name of app you want to migrate schemas');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->package = $input->getArgument('package');
        $this->className = $input->getArgument('model');

        $this->init();
        $this->create();

        return Command::SUCCESS;
    }

    private function init()
    {
        try {
            $this->app = AppManager::getApp($this->package);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->modelFolder = $this->app['path'] . 'model';
    }

    private function create()
    {
        //check availability
        $exportPath = $this->readyModel();
        $this->check();

        try {
            $isCreated = ModelFile::create(
                exportPath: $exportPath,
                className: $this->className,
                package: $this->app['package'],
                namespace: $this->app['namespace'] . DS . 'model'
            );

            if ($isCreated) {
                //print success messages
                $this->success(sprintf('Model created in "%s"', str_replace(['\\', '/'], DS, $exportPath)));
                $this->newLine();
            } else {
                $this->error(sprintf('Same file exist in "%s"!', str_replace(['\\', '/'], DS, $exportPath)));
            }
        } catch (\Exception $e) {
            $this->error($e);
        }
    }

    private function check()
    {
        if (file_exists($this->modelFolder . DS . $this->modelFileName)) {
            $this->error('☓  The model name "' . $this->className . '" already exists ');
        }
    }

    private function readyModel(): string
    {
        //get input
        $this->className = Str::toCamelCase($this->className);
        $this->modelFileName = $this->className . '.php';

        return $this->modelFolder . DS . $this->modelFileName;
    }
}