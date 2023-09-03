<?php

namespace pinoox\command\create;

use pinoox\component\console;
use pinoox\component\File;
use pinoox\component\helpers\PhpFile\MigrationFile;
use pinoox\component\helpers\Str;
use pinoox\component\interfaces\CommandInterface;
use pinoox\component\migration\MigrationToolkit;
use pinoox\component\migration\MigrationConfig as Config;
use pinoox\portal\MigrationConfig;


class createMigration extends console implements CommandInterface
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected string $signature = "create:migration";

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = "Create a new Migration Schema";

    /**
     * The console command Arguments.
     *
     * @var array
     */
    protected array $arguments = [
        ['className', true, 'the name of migration class name', null],
    ];

    /**
     * The console command Options.
     *
     * @var array
     */
    protected $options = [
        ['package', 'p', 'package name of app that you want to migrate.', false],
    ];

    /**
     * @var MigrationToolkit
     */
    private MigrationToolkit $toolkit;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->init();
        $this->create();
    }

    private function init()
    {
        $this->chooseApp($this->option('p'));//init cli

        $this->config = MigrationConfig::load($this->cli['path'], $this->cli['package']);

        if ($this->config->getErrors())
            $this->error($this->config->getLastError());

        $this->toolkit = (new MigrationToolkit())
            ->appPath($this->config->appPath)
            ->migrationPath($this->config->migrationPath)
            ->namespace($this->config->namespace)
            ->package($this->config->package)
            ->ready();
    }

    private function create()
    {
        //get input
        $arg = $this->argument('className');
        $className = Str::toCamelCase($arg);
        $fileName = Str::toUnderScore($className);

        //check availability
        $files = File::get_files($this->config->migrationPath);

        foreach ($files as $f) {
            $name = pathinfo($f, PATHINFO_FILENAME);
            //eliminate timestamp
            $name_no_timestamp = substr($name, 15);
            if ($name_no_timestamp == $fileName) {
                $this->error('☓  The migration class name "' . $className . '" already exists ');
            }
        }

        //create timestamp filename
        $exportFile = date('Ymdhis') . '_' . $fileName . '.php';
        $exportPath = $this->config->migrationPath . $exportFile;
        try {
            $isCreated = MigrationFile::create($exportPath, $className, $this->config->package);
            if ($isCreated) {
                //print success messages
                $this->success('✓ Created Class ' . $className);
                $this->gray(' in path: ' . $this->config->folders);
                $this->warning($exportFile);
                $this->newLine();
            } else {
                $this->error('Can\'t generate a new migration class!');
            }
        } catch (\Exception $e) {
            $this->error($e);
        }

    }


}