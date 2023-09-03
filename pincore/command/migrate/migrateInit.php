<?php

namespace pinoox\command\migrate;


use pinoox\component\console;
use pinoox\component\interfaces\CommandInterface;


class migrateInit extends console implements CommandInterface
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "migrate:init";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Initialize migration repository and create tables";

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
    ];

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->success('Initialized migration table');
        $this->newLine();
        echo $this->execute('migrate', ['pincore'], ['i' => true], false);
    }

}