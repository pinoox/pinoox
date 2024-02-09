<?php

namespace Pinoox\Terminal\Migrate;

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Migration\MigrationToolkit;
use Pinoox\Component\Terminal;
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

    private string $migration;


    /**
     * @var MigrationToolkit
     */
    private MigrationToolkit $mig;

    protected function configure(): void
    {
        $this
            ->addArgument('migration', InputArgument::REQUIRED, 'Enter name of migration name')
            ->addArgument('package', InputArgument::REQUIRED, 'Enter the package name of app you want to migrate schemas');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->package = $input->getArgument('package');
        $this->migration = $input->getArgument('migration');

        $this->init();
        $this->create();

        return Command::SUCCESS;
    }


    private function init(): void
    {
        try {
            $this->mig = new MigrationToolkit();
            $this->mig->package($this->package)->action('create')
                ->load();
        } catch (\Exception $e) {
            $this->error($e);
        }

        if (!$this->mig->isSuccess()) {
            $this->error($this->mig->getErrors());
        }
    }

    private function create(): void
    {
        try {
            $isCreated = StubGenerator::generate('migration.create.stub', $this->getExportPath(), [
                'copyright' => StubGenerator::get('copyright.stub'),
                'table' => $this->mig->getTableName(),
                'namespace' => "App\\$this->package\migrations",
            ]);

            if ($isCreated) {
                //print success messages
                $this->success('✓ Migration [' . $this->mig->getMigrationName() . '] created successfully');
                $this->newLine();
            } else {
                $this->error('Can\'t generate a new migration class!');
            }
        } catch (\Exception $e) {
            $this->error($e);
        }

    }

    private function getExportPath(): string
    {
        //get input
        $this->migration = Str::toCamelCase($this->migration);
        $fileName = Str::toUnderScore($this->migration);

        //check availability
        $finder = new Finder();
        $finder->in($this->mig->getMigrationPath())
            ->files()
            ->name('*.php');

        //create filename
        $this->mig->generateMigrationFileName($fileName);
        return $this->mig->filePath() . '.php';
    }

}