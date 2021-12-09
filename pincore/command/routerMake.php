<?php
namespace pinoox\command;


use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\Config;
use pinoox\component\console;
use pinoox\component\HelperString;
use pinoox\component\interfaces\CommandInterface;
use pinoox\component\Lang;
use pinoox\component\Validation;


class routerMake extends console implements CommandInterface
{

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $signature = "route:make";

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "create new routers.";

	/**
	* The console command Arguments.
	*
	* @var array
	*/
	protected $arguments = [
		[ 'route' , true , 'Route you want to use in url.'  ],
	];

	/**
	* The console command Options.
	*
	* @var array
	*/
	protected $options = [
		['package' , 'p' , 'Package to map with this route.' , null ],
	];

	/**
	* Execute the console command.
	*
	*/
	public function handle()
	{
	    Lang::app('com_pinoox_manager');
		list($message , $status ) = $this->add($this->argument('route'));
		if ( ! $status )
		    $this->error($message);
		if ( $this->option('package') != null ){
            list($message , $status ) = $this->setPackageName($this->argument('route') , $this->option('package'));
            if ( ! $status ){
                $this->remove($this->argument('route'));
                $this->error($message);
            }
            $this->success(sprintf('New route "%s" successfully set for "%s".' , $this->argument('route') , $this->option('package')));
        } else
            $this->success(sprintf('New route "%s" successfully set.' , $this->argument('route') ));
    }


    public function add($alias)
    {
        $routes = Config::get('~app');
        if (empty($alias) || HelperString::has($alias, ['?', '\\', '>', '<', '!', '=', '~', '*', '#']))
            return [rlang('setting>router.write_correct_url'), false];

        if (isset($routes[$alias]))
            return [rlang('setting>router.this_url_exists_before'), false];

        Config::setLinear('~app', $alias, '');
        Config::save('~app');

        return ['', true];
    }

    public function remove($alias)
    {
        if ($alias == '*')
            return ['', false];

        Config::removeLinear('~app',$alias);
        Config::save('~app');

        return ['', true];
    }

    public function setPackageName($alias,$packageName)
    {
        $routes = Config::get('~app');
        if ($alias == 'manager')
            return [rlang('manager.request_not_valid'), false];

        $package = AppModel::fetch_by_package_name($packageName);
        if (empty($package) || !$package['router'])
            return [rlang('manager.request_not_valid'), false];


        if ($package['router'] !== 'multiple' && is_array($routes) && in_array($packageName, $routes))
            return [rlang('manager.request_not_valid'), false];

        if (!Validation::checkOne($alias, 'required') || !isset($routes[$alias]))
            return [rlang('setting>router.no_choose_any_route'), false];

        Config::setLinear('~app', $alias, $packageName);
        Config::save('~app');

        return ['', true];
    }
}