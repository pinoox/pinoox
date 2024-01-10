<?php

namespace Pinoox\Command\migrate;


use Pinoox\Component\console;
use Pinoox\Component\Interfaces\CommandInterface;
use Pinoox\Component\Package\App;
use Pinoox\Portal\DB;
use Pinoox\Portal\MigrationConfig;
use \Pinoox\Component\Migration\MigrationConfig as MigConf;
use Pinoox\Component\Migration\MigrationQuery;
use Pinoox\Component\Migration\MigrationToolkit;


class migrateRun extends console implements CommandInterface
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "migrate";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Migrate schemas";

    /**
     * The console command Arguments.
     *
     * @var array
     */
    protected $arguments = [
    ];

    /**
     * The console command Options.
     *
     * @var array
     */
    protected $options = [
        ['init', 'i', 'to run init', false],
        ['package', 'p', 'package name of app that you want to migrate.', false],
    ];

    private $package;

    /**
     * @var boolean
     */
    private $isInit = true;

    /**
     * @var MigrationToolkit
     */
    private $toolkit = null;

    /**
     * @var MigrationToolkit
     */
    private $schema = null;

    /**
     * @var MigConf
     */
    private MigConf $config;

    /**
     * @var MigrationConfig
     */
    private $mc = null;

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->init();
        $this->runUp();
    }

    private function init()
    {

        $this->package = $this->option('i');
        $this->chooseApp($this->package);//init cli

        $this->config = MigrationConfig::load($this->cli['path'], $this->cli['package']);

        if ($this->config->getErrors())
            $this->error($this->mc->getLastError());

        $this->isInit = $this->option('i');

        $this->toolkit = (new MigrationToolkit())
            ->appPath($this->config->appPath)
            ->migrationPath($this->config->migrationPath)
            ->namespace($this->config->namespace)
            ->package($this->config->package)
            ->action($this->isInit ? 'init' : 'run')
            ->ready();

        if (!$this->toolkit->isSuccess()) {
            $this->error($this->toolkit->getErrors());
        }
        $this->schema = $this->toolkit->getSchema();
    }

    private function runUp()
    {
        $migrations = $this->toolkit->getMigrations();
        if (empty($migrations)) {
            $this->success('Nothing to migrate.');
            $this->newLine();
        }

        $batch = !$this->isInit && MigrationQuery::fetchLatestBatch($this->config->package) ?? 0;

        foreach ($migrations as $m) {
            $start_time = microtime(true);
            $this->warning('Migrating: ');
            $this->info($m['fileName']);
            $this->newLine();

            $obj = new $m['classObject']();
            $obj->prefix = $m['dbPrefix'];
            $obj->up();

            if (!$this->isInit) {
                MigrationQuery::insert($m['fileName'], $m['packageName'], $batch);
            }

            $end_time = microtime(true);
            $exec_time = $end_time - $start_time;

            //end migrating
            $this->success('Migrated: ');
            $this->info($m['fileName']);
            $this->gray(' (' . substr($exec_time, 0, 5) . 'ms)');
            $this->newLine();
        }
    }

}