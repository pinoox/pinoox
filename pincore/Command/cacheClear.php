<?php
namespace Pinoox\Command;


use Pinoox\Component\Console;
use Pinoox\Component\Dir;
use Pinoox\Component\File;
use Pinoox\Component\Interfaces\CommandInterface;


class cacheClear extends Console implements CommandInterface
{

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $signature = "clear:cache";

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "Clear all Cache";

	/**
	* The console command Arguments.
	*
	* @var array
	*/
	protected $arguments = [
        [ 'app_name' , false , 'Name of your application.' , null ],
	];

	/**
	* The console command Options.
	*
	* @var array
	*/
	protected $options = [
		//[ name , short_name , description , default ],
	];

	protected $coreConfig = [
	    'app',
        'database'
    ];

	/**
	* Execute the console command.
	*
	*/
	public function handle()
	{
        $folders = [];
		if ( $this->argument('app_name') == null ){
            $folders = $this->removePinkerFromPath(Dir::path('~'));
            $folders2 = $this->removePinkerFromPath(Dir::path('~pincore'));
            $folders3 = $this->removePinkerFromPath(Dir::path('~apps') , true);
            $folders = array_merge($folders , $folders2 , $folders3);
        } elseif ( $this->argument('app_name') == '~' ){
            $folders = $this->removePinkerFromPath(Dir::path('~pincore'));
        } else {
            $app = AppHelper::fetch_by_package_name($this->argument('app_name') );
            if ( ! is_null($app) )
                $folders = $this->removePinkerFromPath(Dir::path('~apps/'.$this->argument('app_name') ) , true);
        }
		foreach ($folders as $folder){
		    $this->success(sprintf('cache clear in `%s`.' , str_replace('\\','/', $folder)));
		    $this->newLine();
        }
        $this->success('all cache cleared.');
	}

	private function removePinkerFromPath($path, $checkSubDire = false){
	    $folders = [];
        if( is_dir($path.'/pinker/cache/')) {
            $folders[] = $path .'/pinker/cache/';
            File::remove($path . '/pinker/cache/');
        }
        if ($checkSubDire) {
            $dirs = File::get_dir_folders($path);
            foreach ($dirs as $dir) {
                $subFolders = $this->removePinkerFromPath($dir, $checkSubDire);
                $folders = array_merge($folders , $subFolders);
            }
        }
        return $folders;
    }
}