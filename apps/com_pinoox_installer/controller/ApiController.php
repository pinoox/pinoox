<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\app\com_pinoox_installer\controller;

use pinoox\component\Dir;
use pinoox\component\helpers\HelperArray;
use pinoox\component\kernel\controller\Controller;
use pinoox\component\Lang;
use pinoox\component\Http\Request;
use pinoox\component\migration\Migrator;
use pinoox\component\Security;
use pinoox\component\System;
use pinoox\component\Validation;
use pinoox\component\Request as RequestData;
use pinoox\model\UserModel;
use pinoox\portal\app\App;
use pinoox\portal\app\AppEngine;
use pinoox\portal\app\AppRouter;
use pinoox\portal\Config;
use pinoox\portal\DB;

class ApiController extends Controller
{
    public function changeLang($lang): array
    {
        $lang = strtolower($lang);
        App::set('lang', $lang);
        App::set('lang', $lang)
            ->save();
        Lang::change($lang);
        return $this->getLang();
    }

    protected function getLang($lang = null): array
    {
        $lang = empty($lang) ? App::get('lang') : $lang;
        return [
            'direction' => in_array($lang, ['fa', 'ar']) ? 'rtl' : 'ltr',
            'lang' => [
                'install' => rlang('install'),
                'user' => rlang('user'),
                'language' => rlang('language'),
            ]
        ];
    }

    private function checkConnect($data): bool
    {
        $isConnect = false;
        try {
            $mysqli = new \mysqli($data['host'], $data['username'], $data['password'], $data['database']);
            $isConnect = !$mysqli->connect_error;
        } catch (\Exception $e) {
        }

        return $isConnect;
    }

    public function checkDB(Request $request)
    {
        $data = RequestData::input('host,database,username,password,prefix', '', '!empty');

        if ($this->checkConnect($data)) {
            return $this->message('connect', true);
        }

        return $this->message('disconnect', false);
    }

    public function checkPrerequisites($type)
    {
        $status = false;
        switch ($type) {
            case 'php' :
                $status = (bool)version_compare(System::phpVersion(), '5.6', '>=');
                break;
            case 'mysql' :
                /**
                 * fixme: Version mysql cannot be found on all operating systems
                 */
                // $vMysql = System::mysqlVersion();
                $status = true;//(!empty($vMysql)) ? version_compare($vMysql, '5.5', '>=') : true;
                break;
            case 'free_space' :
                /**
                 * fixme: The required space cannot be found on all operating systems
                 */
                $status = true;//(System::freeSpace('MB') > 50);
                break;
            case 'mod_rewrite' :
                /**
                 * fixme: The mod_rewrite status cannot be found on all operating systems
                 */
                $status = true;//System::hasModuleApache('mod_rewrite');
                break;
        }


        return $this->message($type, $status);
    }

    public function agreement()
    {
        return rlang('agreement');
    }

    public function setup(Request $request)
    {
        $inputs = RequestData::input('user,db', [], '!empty');
        $user = HelperArray::parseParams($inputs['user'], 'fname,lname,username,password,email', null, '!empty');
        $db = HelperArray::parseParams($inputs['db'], 'host,database,username,password,prefix', null, '!empty');

        $valid = Validation::check($user, [
            'fname' => ['required|length:>2', rlang('user.name')],
            'lname' => ['required|length:>2', rlang('user.family_name')],
            'email' => ['required|email', rlang('user.email')],
            'username' => ['required|length:>2|username', rlang('user.username')],
            'password' => ['required|length:>5', rlang('user.password')],
        ]);

        if ($valid->isFail())
            return $this->message($valid->first(), false);

        if (!$this->insertTables($db, $user)) {
            return $this->message(rlang('install.err_insert_tables'), false);
        }

        $appRoutes = Config::name('app')->get();
        AppRouter::setData($appRoutes);

        $lang = App::get('lang');
        App::set('enable', false)
            ->save();

        // change lang app welcome
        AppEngine::config('com_pinoox_welcome')
            ->set('lang', $lang)
            ->save();

        // change lang app manager
        AppEngine::config('com_pinoox_manager')
            ->set('lang', $lang)
            ->save();

        return $this->message('success', true);
    }

    private function insertTables($c, $u)
    {
        if (empty($c) || empty($u))
            return false;

        if (!$this->checkConnect($c)) return false;

        $data = [
            'driver' => 'mysql',
            'host' => $c['host'],
            'port' => '3306',
            'database' => $c['database'],
            'username' => $c['username'],
            'password' => $c['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_bin',
            'prefix' => $c['prefix'],
            'strict' => true,
            'engine' => null,
        ];

        Config::name('~database')
            ->set('production', $data)
            ->set('development', $data)
            ->save();

        //TODO migrate init & run

        $migrator = new Migrator('pincore');
        try {
            $migrator->run();
        } catch (\Exception $e) {
        }


        $user = new UserModel();
        $user->app = 'pincore';
        $user->fname = $u['fname'];
        $user->lname = $u['lname'];
        $user->username = $u['username'];
        $user->password = Security::passHash($u['password']);
        $user->email = $u['email'];
        $user->status = UserModel::active;
        return $user->save();
    }

    private function message($result, $status)
    {
        return ["status" => $status, "result" => $result];
    }
}
    
