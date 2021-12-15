<?php
namespace pinoox\command;


use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\console;
use pinoox\component\HelperString;
use pinoox\component\interfaces\CommandInterface;
use pinoox\component\Lang;
use pinoox\model\UserModel;


class repassword extends console implements CommandInterface
{

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $signature = "user:password";

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "Change password of user";

	/**
	* The console command Arguments.
	*
	*	[ name , is_required , description , default ],
	*
	* @var array
	*/
	protected $arguments = [
        ['username', true, 'username or email of user that you want to update password.' , null ],
        ['package', false, 'name of package that user register inside that.' , null ],
	];

	/**
	* The console command Options.
	*
	*	[ name , short_name , description , default ],
	*
	* @var array
	*/
	protected $options = [
        [ 'password' , 'p' , 'new password.' , 'Pinoox random string!' ],
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

        $packageSelected = $this->argument('package');
        if ( $packageSelected == null ){
            $apps = AppModel::fetch_all(null , true);
            $packages = array_keys($apps);
            $choice = array_column($apps , 'name' );
            $appId = $this->choice('Please select package of user register on that.',  $choice );
            $packageSelected = isset($packages[$appId]) ? $packages[$appId] : null ;
            $nameSelected = isset($choice[$appId]) ? $choice[$appId] : null ;
            if ( $packageSelected == null ){
                $this->error('Can not find selected package!');
            }
        }
        $user = UserModel::fetch_user_by_email_or_username($this->argument('username') , null , $packageSelected);
        if (empty($user)) {
            $this->error(sprintf('Can nor find `%s` in `%s`' , $this->argument('username') , $nameSelected ) );
        }
		$password = $this->option('password');
        if ( $password == 'Pinoox random string!' )
            $password = HelperString::generateRandom();

        UserModel::update_password($user['user_id'],$password , null , $packageSelected );
        $result = UserModel::fetch_by_password($user['user_id'],$password );
        if ( is_null($result) )
            $this->error('Can not update password!');
        $this->success(sprintf('Password of `%s` in `%s` updated to :' , $this->argument('username') , $nameSelected) );
        $this->newLine();
        $this->danger($password);
        $this->newLine();
        $this->newLine();
        $this->warning('Please change this password as soon as you can!');
	}

}