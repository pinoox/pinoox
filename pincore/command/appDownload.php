<?php

namespace pinoox\command;


use pinoox\portal\Config;
use pinoox\component\Console;
use pinoox\component\Download;
use pinoox\component\helpers\HelperHeader;
use pinoox\component\helpers\HelperString;
use pinoox\component\HttpRequest;
use pinoox\component\interfaces\CommandInterface;
use pinoox\component\Request;
use pinoox\component\Response;
use pinoox\component\Url;


class appDownload extends Console implements CommandInterface
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "app:download";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Download app from Pinoox market";

    /**
     * The console command Arguments.
     *
     *    [ name , is_required , description , default ],
     *
     * @var array
     */
    protected $arguments = [
        ['application', false, 'key word of application you want.', null],
    ];

    /**
     * The console command Options.
     *
     *    [ name , short_name , description , default ],
     *
     * @var array
     */
    protected $options = [
        ['install', 'i', 'is install after download application?', true],
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
        $this->warning('Search list of application.');
        $this->newLine();
        $listApplication = $this->getApps();
        $this->moveUp();
        $this->clearLine();
        $choices = [];
        foreach ($listApplication['items'] as $application) {
            $choices[$application['package_name']] = $this->getColoredString($application['app_name'], 'green') . ' ' .
                $this->getColoredString($application['summary'], 'gray') . ' [' .
                $this->getColoredString($application['package_name'], 'blue') . ']';
        }
        $application = $this->choice('Witch one of this application you want?', $choices, 'nothing');
        if ($application == 'nothing')
            exit;

        $this->warning('Login to pinoox server. please wait!');
        $this->newLine();
        Config::app('com_pinoox_manager');
        $token = $this->login();
        $reTry = true;
        while ($token == false and $reTry) {
            $this->danger('Email and Password is not match!');
            $reTry = $this->confirm('Do you want to try login?');
            if ($reTry)
                $token = $this->login();
        }

        $this->warning(sprintf('Download application `%s` from pinoox server. please wait!', $application));
        $this->newLine();
        $this->downloadRequest($application, $token);
        $this->success(sprintf('Application `%s` downloaded successfully.', $application));
        $this->newLine();

        if ($this->hasOption('install', $this->options))
            appInstall::install($application);
    }

    private function login()
    {
        $token_key = Config::name('connect')->get('token_key');
        if (!is_null($token_key)) {
            $data2 = HttpRequest::init('https://www.pinoox.com/api/manager/v1/account/getData', HttpRequest::POST, false)->params([
                'remote_url' => 'http://pinoox-cli/',
                'token_key' => $token_key,
            ])->options([
                'type' => HttpRequest::form,
                'timeout' => 8000
            ])->send();
            $data2 = json_decode($data2, true);
            if ($data2['status']) {
                return $token_key;
            } else {
                Config::name('connect')
                    ->set('token_key', null)
                    ->save();
                $token_key = null;
            }
        }
        if (is_null($token_key)) {
            $username = $this->input_dialog('Please enter https://www.Pinoox.com Email:');
            $password = $this->input_dialog();
            $data3 = HttpRequest::init('https://www.pinoox.com/api/manager/v1/account/login', HttpRequest::POST, false)->params([
                'email' => $username,
                'password' => $password,
                'remote_url' => 'http://pinoox-cli/'
            ])->options([
                'type' => HttpRequest::form,
                'timeout' => 8000
            ])->send();
            $data3 = json_decode($data3, true);
            if ($data3['status']) {
                Config::name('connect')
                    ->set('token_key', $data3['result']['token'])
                    ->save();
                return $data3['result']['token'];
            } else
                return false;
        }
    }

    private function getApps()
    {
        $keyword = $this->argument('application') ? $this->argument('application') : '';
        $data = HttpRequest::init('https://www.pinoox.com/api/manager/v1/market/get/' . $keyword, HttpRequest::GET, false)->send();
        return json_decode($data, true);
    }

    private function downloadRequest($packageName, $token)
    {
        $app = AppHelper::fetch_by_package_name($packageName);
        if (!empty($app))
            $this->error('This application exist in your applications!');

        $version_name = Config::name('~pinoox')->get('version_name');
        $params = [
            'token' => $token,
            'remote_url' => 'http://pinoox-cli/',
            'user_agent' => HelperHeader::getUserAgent() . ';Pinoox/' . $version_name . ' Manager;pinoox-cli',
        ];

        $res = HttpRequest::init('https://www.pinoox.com/api/manager/v1/market/downloadRequest/' . $packageName, HttpRequest::POST, false)->params($params)->send();
        if (!empty($res)) {
            $response = json_decode($res, true);
            if (!$response['status']) {
                $this->error('Can not download it now!');
            } else {
                $path = path("downloads>apps>" . $packageName . ".pin", 'com_pinoox_manager');
                $_SERVER['HTTP_USER_AGENT'] = 'pinoox-cli';
                Download::fetch('https://www.pinoox.com/api/manager/v1/market/download/' . $response['result']['hash'], $path)->process();
                Config::name('market')
                    ->set($packageName, $response['result'])
                    ->save();
                return true;
            }
        }
    }
}