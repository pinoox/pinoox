<?php
namespace pinoox\command;


use PHPUnit\TextUI\Command;
use pinoox\component\console;
use pinoox\component\HelperString;
use pinoox\component\interfaces\CommandInterface;


class unitTest extends console implements CommandInterface
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
	protected $description = "Run PHPUnit for testing applications.";

	/**
	* The console command Arguments.
	*
	*	[ name , is_required , description , default ],
	*
	* @var array
	*/
	protected $arguments = [
		[ 'path_file' , false , "Path directory or file of test." , "" ],
	];

	/**
	* The console command Options.
	*
	*	[ name , short_name , description , default ],
	*
	* @var array
	*/
	protected $options = [
		[ 'testdox' , null , 'Report test execution progress in TestDox format' , null ],
		[ 'printer' , null , 'How to show result.[\'default\' , \'collision\' , <name_of_class>]' , 'collision' ],
		[ 'more_help' , 'mh' , 'Get PHPUnit helps options!' , null ],
	];

	/**
	* Execute the console command.
	*
	* use full method :
	*   $this->option(string $key) : string|null|bool
	*   $this->argument(string $key) : string|null|bool
	*   $this->hasOption(string $key) : bool
	*   $this->success(string $text) : void
	*   $this->danger(string $text) : void
	*   $this->warning(string $text) : void
	*   $this->info(string $text) : void
	*   $this->gray(string $text) : void
	*   $this->newLine(string $text) : void
	*   $this->error(string $text, bool $exit = true) : void
	*   $this->choice(string $question, array $choices, mix $default = null, bool $multiple = false, int $attempts = 2 ) : string|int|array
	*   $this->confirm(string $operation) : bool
	*   $this->table(array $headers, 2D_array $rows) : void
	*   $this->startProgressBar(int $jobs = 1, int $totalJobs = 0 ) : void
	*   $this->nextStepProgressBar(string $operation) : void
	*   $this->finishProgressBar(string $description = "") : void
	*
	*/
	public function handle()
	{
        if ((int) \PHPUnit\Runner\Version::id()[0] < 9) {
            $this->error('Running Collision ^5.0 artisan test command requires at least PHPUnit ^9.0.');
        }

        if (version_compare('7.3.0', PHP_VERSION, '>')) {
            $this->warning('This version of PHPUnit is supported on PHP 7.3, PHP 7.4, and PHP 8.0.');
            $this->error(sprintf('You are using PHP %s (%s).', PHP_VERSION, PHP_BINARY));
        }

        if (!ini_get('date.timezone')) {
            ini_set('date.timezone', 'UTC');
        }

        $file = path('~/vendor/autoload.php');
        if (file_exists($file)) {
            define('PHPUNIT_COMPOSER_INSTALL', $file);
        } else {
            $this->warning('You need to set up the project dependencies using Composer:');
            $this->newLine();
            $this->warning('    composer install');
            $this->newLine();
            $this->warning('You can learn all about Composer on https://getcomposer.org/.');
            $this->error('Please install composer');

        }
        unset($file);
        $options = getopt('', array('prepend:'));

        if (isset($options['prepend'])) {
            require $options['prepend'];
        }
        unset($options);
        $_SERVER['argv']  = array_slice($_SERVER['argv'] , 1);
        $_SERVER['argv'][1] = 'tests/' . $this->argument('path_file');
        if ( $this->option('printer') == "collision")
            $_SERVER['argv'][] = '--printer=NunoMaduro\\Collision\\Adapters\\Phpunit\\Printer';
        elseif ( $this->option('printer') == "default")
            foreach ( $_SERVER['argv'] as $index => $argv ) {
                if ( HelperString::firstHas($argv , '--printer=') ){
                    unset($_SERVER['argv'] [$index]);
                    break;
                }
            }
        if ( $this->hasOption('more_help' , $this->options) )
            $_SERVER['argv'] = ['-h'];
        Command::main();
    }
}