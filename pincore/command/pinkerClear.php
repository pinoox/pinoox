<?php
namespace pinoox\command;


use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\console;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\interfaces\CommandInterface;


class pinkerClear extends console implements CommandInterface
{

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $signature = "clear:pinker";

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "Clear all pinker folder";

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
	    if ( $this->confirm('Are you sure want to delete all `pinker` folder?')) {
            $folders = [];
            if ($this->argument('app_name') == null) {
                $folders = $this->removePinkerFromPath(Dir::path('~'));
                $folders2 = $this->clearCoreCache();
                $folders3 = $this->removePinkerFromPath(Dir::path('~apps'), true);
                $folders = array_merge($folders, $folders2, $folders3);
            } elseif ($this->argument('app_name') == '~') {
                $folders = $this->clearCoreCache();
            } else {
                $app = AppModel::fetch_by_package_name($this->argument('app_name'));
                if (!is_null($app))
                    $folders = $this->removePinkerFromPath(Dir::path('~apps/' . $this->argument('app_name')), true);
            }
            foreach ($folders as $folder) {
                $this->success(sprintf('pinker deleted in `%s`.', $folder));
                $this->newLine();
            }
            $this->success('all pinker folder deleted.');
        } else
            $this->success('Clean pinker folder canceled.');
	}

	private function removePinkerFromPath($path, $checkSubDire = false){
	    $folders = [];
        if( is_dir($path.DIRECTORY_SEPARATOR.'pinker'.DIRECTORY_SEPARATOR)) {
            $folders[] = $path . DIRECTORY_SEPARATOR . 'pinker' . DIRECTORY_SEPARATOR;
            File::remove($path . DIRECTORY_SEPARATOR . 'pinker' . DIRECTORY_SEPARATOR);
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

    private function clearCoreCache(){
	    foreach ( $this->coreConfig as $file )
            @rename(Dir::path('~pincore/pinker/config/'.$file.'.config.php') , Dir::path('~pincore/'.$file.'.config.php'));

        $folders = $this->removePinkerFromPath(Dir::path('~pincore'));

        File::make_folder(Dir::path('~pincore/pinker/') , false, 0777 , false);
        File::make_folder(Dir::path('~pincore/pinker/config/') , false, 0777 , false);
        foreach ( $this->coreConfig as $file )
            @rename(Dir::path('~pincore/'.$file.'.config.php') , Dir::path('~pincore/pinker/config/'.$file.'.config.php') );

        return $folders;
    }
}