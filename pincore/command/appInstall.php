<?php
namespace pinoox\command;


use pinoox\app\com_pinoox_manager\component\Wizard;
use pinoox\component\console;
use pinoox\component\interfaces\CommandInterface;
use pinoox\component\Response;
use pinoox\component\Router;


class appInstall extends console implements CommandInterface
{

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $signature = "app:install";

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "Install application.";

	/**
	* The console command Arguments.
	*
	*	[ name , is_required , description , default ],
	*
	* @var array
	*/
	protected $arguments = [
        [ 'application' , true , 'Application name that should be install.' , null ],
	];

	/**
	* The console command Options.
	*
	*	[ name , short_name , description , default ],
	*
	* @var array
	*/
	protected $options = [
		//[ name , short_name , description , default ],
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
	*   $this->newLine() : void
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
		self::install($this->argument('application'));
	}

	public static function install($application){
        Router::setApp('com_pinoox_manager');
        self::warning(sprintf('Start installing `%s`. please wait!' , $application) );
        self::newLine();
        if (empty($application))
            self::error('app install request is invalid 1');

        $pinFile = Wizard::get_downloaded($application);
        if (!is_file($pinFile))
            self::error('app install request is invalid 2');

        if (Wizard::installApp($pinFile)) {
            self::success('Application successfully installed.');
        } else {
            $message = Wizard::getMessage();
            if (empty($message))
                self::error('app install request is invalid 3');
            else{
                self::danger($message);
                self::error('app install request is invalid 4');
            }
        }
    }

}