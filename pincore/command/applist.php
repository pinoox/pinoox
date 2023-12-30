<?php
namespace Pinoox\Command;


use Pinoox\Component\Console;
use Pinoox\Component\Interfaces\CommandInterface;


class applist extends Console implements CommandInterface
{

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $signature = "app:list";

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "get list of all application.";

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
		$apps = AppHelper::fetch_all(null , true);
		$result = [];
		foreach ($apps as $app){
            $result[] = [
                $app['package_name'],
                $app['name']? $app['name'] : '-',
                $app['description']? $app['description'] : '-',
                $app['version']? $app['version'] : '-',
                $app['hidden']? "Hidden" : "-",
            ];
        }
		$this->table(['Package name' , 'Name' , 'Description' , 'Version', 'Hidden' ] , $result);
	}

}