<?php

namespace pinoox\command\migrate;


use pinoox\component\console;
use pinoox\component\interfaces\CommandInterface;
use pinoox\component\migration\MigrationConfig;
use pinoox\component\migration\MigrationQuery;
use pinoox\component\migration\MigrationToolkit;


class migrateStatus extends console implements CommandInterface
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "migrate:status";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Show the status of each migration";

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

    private $package;

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
        $this->status();
    }

    private function init()
    {
        $this->package = $this->argument('package');
        $this->chooseApp($this->package);//init cli

        $this->mc = new MigrationConfig($this->cli['path'], $this->cli['package']);
        $this->mc->load();

        if ($this->mc->getErrors())
            $this->error($this->mc->getLastError());

        $this->toolkit = (new MigrationToolkit())
            ->appPath($this->mc->appPath)
            ->migrationPath($this->mc->migrationPath)
            ->namespace($this->mc->namespace)
            ->package($this->mc->package)
            ->action('status')
            ->ready();

        $this->schema = $this->toolkit->getSchema();
    }

    private function status()
    {
        $migrations = $this->toolkit->getMigrations();

        if (empty($migrations)) {
            $this->success('There are no migrations!');
            $this->newLine();
            exit;
        }

        $this->info('Migration status:');
        $this->newLine();
        $this->table(['Migration', 'Batch', 'Status'], $this->getRows($migrations));
    }

    private function getRows($migrations)
    {
        $rows = [];
        foreach ($migrations as $m) {
            $status = !empty($m['sync']) ? 'Done' : 'Pending';
            $batch = $m['sync']['batch'] ?? null;
            $rows[] = [
                $m['fileName'],
                $batch,
                $status
            ];
        }
        return $rows;
    }

}