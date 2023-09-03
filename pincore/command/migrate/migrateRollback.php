<?php

namespace pinoox\command\migrate;


use pinoox\component\console;
use pinoox\component\interfaces\CommandInterface;
use pinoox\component\package\App;
use pinoox\portal\MigrationConfig;
use \pinoox\component\migration\MigrationConfig as MigConf;
use pinoox\component\migration\MigrationQuery;
use pinoox\component\migration\MigrationToolkit;


class migrateRollback extends console implements CommandInterface
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "migrate:rollback";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Rollback the database migrations";

    /**
     * The console command Arguments.
     *
     * @var array
     */
    protected $arguments = [
        ['package', false, 'package name of app that you want to migrate.', null],
    ];

    /**
     * The console command Options.
     *
     * @var array
     */
    protected $options = [
    ];

    /**
     * @var MigConf
     */
    private MigConf $config;

    /**
     * @var MigrationConfig
     */
    private $mc = null;

    /**
     * @var MigrationToolkit
     */
    private $toolkit = null;

    /**
     * @var MigrationToolkit
     */
    private $schema = null;

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->init();
        $this->reverseDown();
    }

    private function init()
    {
        $this->chooseApp($this->argument('package'));//init cli

        $this->config = MigrationConfig::load($this->cli['path'], $this->cli['package']);

        if ($this->config->getErrors())
            $this->error($this->mc->getLastError());

        $this->toolkit = (new MigrationToolkit())
            ->appPath($this->config->appPath)
            ->migrationPath($this->config->migrationPath)
            ->namespace($this->config->namespace)
            ->package($this->config->package)
            ->action('rollback')
            ->ready();

        $this->schema = $this->toolkit->getSchema();
    }

    private function reverseDown()
    {
        $migrations = $this->toolkit->getMigrations();

        if (empty($migrations)) {
            $this->success('Nothing to rollback.');
            $this->newLine();
        }

        $batch = MigrationQuery::fetchLatestBatch($this->config->package);

        foreach ($migrations as $m) {

            if (!$m['isLoad']) {
                $this->danger('Migration not found: ');
                $this->info($m['fileName']);
                $this->newLine();
                continue;
            }

            $start_time = microtime(true);
            $this->warning('Rolling back: ');
            $this->info($m['fileName']);
            $this->newLine();
            $obj = new $m['classObject']();
            $obj->prefix = $m['dbPrefix'];
            $obj->down();

            MigrationQuery::delete($batch, $m['packageName']);

            $end_time = microtime(true);
            $exec_time = $end_time - $start_time;

            //end migrating
            $this->success('Rolled back: ');
            $this->info($m['fileName']);
            $this->gray(' (' . substr($exec_time, 0, 5) . 'ms)');
            $this->newLine();
        }

    }

}