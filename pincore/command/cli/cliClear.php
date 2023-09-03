<?php

namespace pinoox\command\cli;


use pinoox\component\Config;
use pinoox\component\console;
use pinoox\component\interfaces\CommandInterface;


class cliClear extends console implements CommandInterface
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "cli:clear";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Clear default app in config";

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
        Config::set('~cli.path', null);
        Config::set('~cli.package', null);
        Config::save('~cli');

        $this->success('Cleared CLI config');
    }

}