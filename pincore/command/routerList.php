<?php
namespace pinoox\command;



use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\Config;
use pinoox\component\console;
use pinoox\component\interfaces\CommandInterface;


class routerList extends console implements CommandInterface
{

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $signature = "route:list";

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "List of routing rules.";

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
        $this->info('List Of all routing rules:');
        $this->table(['Route' , 'Package' ] , $this->listRoute());
	}

	private function listRoute(){
        $routes = Config::get('~app');
        $result = [];
        if (!empty($routes)) {
            foreach ($routes as $alias => $packageName) {
                if ( $packageName == "" )
                    $packageName = "Nothing !";
                $result[] =[$alias , $packageName ];
            }
        }
        return $result;
    }

}