<?php

namespace Pinoox\Terminal\Model;

use Pinoox\Component\Helpers\Str;
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
    name: 'model:create',
    description: 'Create a new Eloquent model class in an app',
    aliases: ['make:model'],
)]

class ModelCreateCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Creates a model stub inside apps/{package}/Model/.

Example:

  php pinoox model:create ProductModel com_my_shop

HELP
            )
            ->addArgument('model', InputArgument::REQUIRED, 'Model class name (e.g. ProductModel)')
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $model = $input->getArgument('model');
        $package = $this->resolvePackageRequired($input, $output, $io, [
            'sectionTitle' => 'Create model in',
        ]);
        $table = Str::toUnderScore($model);
        $table = str_replace(['\\', '\\_'], '', $table);

        $stub = new StubBuilderHelper($model, $package, 'model');
        $isCreated = $stub->generate('model.create.stub', [
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

