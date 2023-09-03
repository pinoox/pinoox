<?php
namespace pinoox\command;


use pinoox\app\com_pinoox_manager\component\Wizard;
use pinoox\component\Console;
use pinoox\component\interfaces\CommandInterface;
use pinoox\component\Router;


class appDelete extends Console implements CommandInterface
{

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $signature = "app:remove";

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "Remove application!";

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
        Router::setApp('com_pinoox_manager');
        $application = $this->argument('application')  ;
        $confirm =  $this->confirm(sprintf('Are you sure to REMOVE `%s` ?' , $application));
        if ( $confirm ){
            $this->warning(sprintf('Start removing `%s`. please wait!' , $application) );
            $this->newLine();
            Wizard::deleteApp($application);
            $this->success(sprintf('Application `%s` Removed successfully.' , $application) );
        }
	}

}