<?php
namespace pinoox\command;


use pinoox\component\console;
use pinoox\component\interfaces\CommandInterface;


class tableProgressBar extends console implements CommandInterface
{

	/**
	* The console command name.
	*
	* @var string
	*/
	protected string $signature = "test";

	/**
	* The console command description.
	*
	* @var string
	*/
	protected string $description = "Simple command for show table and progressbar";

	/**
	* The console command Arguments.
	*
	* @var array
	*/
	protected array $arguments = [
		//[ name , is_required , description , default ],
	];

	/**
	* The console command Options.
	*
	* @var array
	*/
	protected array $options = [
		//[ name , short_name , description , default ],
	];

	/**
	* Execute the console command.
	*
	*/
	public function handle()
	{
		$header = [
		    'column 1' , 'column 2'
        ];
		$data = [
		    ['Row 1 Column 1' , 'Row 1 Column 2'],
		    ['Row 2 Column 1' , 'Row 2 Column 2 with different width !'],
		    ['Row 3 Column 1' , 'Row 2 Column 2'],
        ];
		$this->table($header , $data);
		$this->startProgressBar(10 , "test ProgressBar");
		for ( $i = 0 ; $i < 10 ; $i++){
            sleep(1);
		    $this->nextStepProgressBar();
        }
        $this->finishProgressBar();
	}

}