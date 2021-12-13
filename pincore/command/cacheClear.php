<?php
namespace pinoox\command;


use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\console;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\interfaces\CommandInterface;


class cacheClear extends console implements CommandInterface
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
            $app = AppModel::fetch_by_package_name($this->argument('app_name') );
            if ( ! is_null($app) )
                $folders = $this->removePinkerFromPath(Dir::path('~apps/'.$this->argument('app_name') ) , true);
        }
		foreach ($folders as $folder){
		    $this->success(sprintf('cache clear in `%s`.' , $folder));
		    $this->newLine();
        }
        $this->success('all cache cleared.');
	}

	private function removePinkerFromPath($path, $checkSubDire = false){
	    $folders = [];
        if( is_dir($path.DIRECTORY_SEPARATOR.'pinker'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR)) {
            $folders[] = $path . DIRECTORY_SEPARATOR . 'pinker' . DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
            File::remove($path . DIRECTORY_SEPARATOR . 'pinker' . DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR);
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