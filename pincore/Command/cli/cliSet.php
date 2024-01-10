<?php

namespace Pinoox\Command\cli;


use Pinoox\Component\Config;
use Pinoox\Component\console;
use Pinoox\Component\Interfaces\CommandInterface;


class cliSet extends console implements CommandInterface
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "cli:set";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Set a default app to use in cli";

    /**
     * The console command Arguments.
     *
     * @var array
     */
    protected $arguments = [
        ['package', false, 'Name of package that you want to work with that', null],
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
        $app  = $this->chooseFromApps();

        Config::set('~cli.path', $app['path']);
        Config::set('~cli.package', $app['package']);
        Config::save('~cli');

        $this->success('Saved in CLI config');
    }

}