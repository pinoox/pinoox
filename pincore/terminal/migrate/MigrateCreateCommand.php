<?php

namespace pinoox\terminal\migrate;

use pinoox\component\Helpers\Str;
use pinoox\component\Terminal;
use pinoox\portal\AppManager;
use pinoox\portal\StubGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use pinoox\portal\MigrationToolkit;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(
    name: 'migrate:create',
    description: 'Create a new Migration Schema.',
)]
class MigrateCreateCommand extends Terminal
{
    private string $package;

    private array $app;

    private string $className;


    /**
     * @var MigrationToolkit
     */
    private $toolkit = null;

    protected function configure(): void
    {
        $this
            ->addArgument('className', InputArgument::REQUIRED, 'Enter name of migration class name')
            ->addArgument('package', InputArgument::REQUIRED, 'Enter the package name of app you want to migrate schemas');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->package = $input->getArgument('package');
        $this->className = $input->getArgument('className');

        $this->init();
        $this->create();

        return Command::SUCCESS;
    }


    private function init(): void
    {
        try {
            $this->app = AppManager::getApp($this->package);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->toolkit = MigrationToolkit::appPath($this->app['path'])
            ->migrationPath($this->app['migration'])
            ->package($this->app['package'])
            ->namespace($this->app['namespace'])
            ->action('create')
            ->load();

        if (!$this->toolkit->isSuccess()) {
            $this->error($this->toolkit->getErrors());
        }
    }

    private function create(): void
    {
        //get input
        $this->className = Str::toCamelCase($this->className);
        $fileName = Str::toUnderScore($this->className);

        //check availability
        $finder = new Finder();
        $finder->in($this->app['migration'])
            ->files()
            ->filter(static function (SplFileInfo $file) {
                return $file->isDir() || \preg_match('/\.(php)$/', $file->getPathname());
            });


        //create filename
        $migrationFilename = $this->toolkit->generateMigrationFileName($fileName);
        $exportPath = $this->app['migration'] . '/' . $migrationFilename;

        try {
            $isCreated = StubGenerator::generate('migration.create.stub', $exportPath, [
                'copyright' => StubGenerator::get('copyright.stub'),
                'table' => $migrationFilename,
            ]);

            if ($isCreated) {
                //print success messages
                $this->success('✓ Created Class ' . $this->className);
                $this->success(' in path: ' . $this->app['migration']);
                $this->warning('/' . $migrationFilename);
                $this->newLine();
            } else {
                $this->error('Can\'t generate a new migration class!');
            }
        } catch (\Exception $e) {
            $this->error($e);
        }

    }


}