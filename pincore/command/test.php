<?php

namespace Pinoox\Command;


use App\com_pinoox_manager\Component\Notification;
use Pinoox\Portal\Config;
use Pinoox\Component\Console;
use Pinoox\Component\Helpers\HelperString;
use Pinoox\Component\HttpRequest;
use Pinoox\Component\Interfaces\CommandInterface;
use Pinoox\Component\Lang;
use Pinoox\Component\Request;
use Pinoox\Component\Url;
use Symfony\Component\HttpClient\HttpClient;


class test extends Console implements CommandInterface
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "test";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Check version of pinoox.";

    /**
     * The console command Arguments.
     *
     * @var array
     */
    protected $arguments = [
        //[ name , is_required , description , default ],
    ];

    /**
     * The console command Options.
     *
     * @var array
     */
    protected $options = [
        //[ name , short_name , description , default ],
    ];

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        exec('composer install --no-dev',$out);
        dump($out);
        //self::info();
        //self::newLine();
        exec('composer install',$out);
        dump($out);
        //self::info($out);
        //self::newLine();
    }
}