<?php

namespace Pinoox\Terminal\Migrate;

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Terminal;
use Pinoox\Portal\MigrationToolkit;
use Pinoox\Portal\StubGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(
    name: 'migrate:create',
    description: 'Create a new Migration Schema.',
)]
class MigrateCreateCommand extends Terminal
{
    private string $package;

    private string $modelName;


    /**
     * @var MigrationToolkit
     */
    private $toolkit = null;

    protected function configure(): void
    {
        $this
            ->addArgument('modelName', InputArgument::REQUIRED, 'Enter name of migration model name')
            ->addArgument('package', InputArgument::REQUIRED, 'Enter the package name of app you want to migrate schemas');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->package = $input->getArgument('package');
        $this->modelName = $input->getArgument('modelName');

        $this->init();
        $this->create();

        return Command::SUCCESS;
    }


    private function init(): void
    {
        $this->toolkit = MigrationToolkit::package($this->package)
            ->action('create')
            ->load();

        if (!$this->toolkit->isSuccess()) {
            $this->error($this->toolkit->getErrors());
        }
    }

    private function create(): void
    {
        //get input
        $this->modelName = Str::toCamelCase($this->modelName);
        $fileName = Str::toUnderScore($this->modelName);

        //check availability
        $finder = new Finder();
        $finder->in($this->toolkit->getMigrationPath())
            ->files()
            ->filter(static function (SplFileInfo $file) {
                return $file->isDir() || \preg_match('/\.(php)$/', $file->getPathname());
            });

        //create filename
        $this->toolkit->generateMigrationFileName($fileName);
        $exportPath = $this->toolkit->filePath() . '.php';

        try {
            $isCreated = StubGenerator::generate('migration.create.stub', $exportPath, [
                'copyright' => StubGenerator::get('copyright.stub'),
                'table' => $this->toolkit->getTableName(),
            ]);

            if ($isCreated) {
                //print success messages
                $this->success('✓ Migration [' . $this->toolkit->getMigrationName() . '] created successfully');
                $this->newLine();
            } else {
                $this->error('Can\'t generate a new migration class!');
            }
        } catch (\Exception $e) {
            $this->error($e);
        }

    }


}