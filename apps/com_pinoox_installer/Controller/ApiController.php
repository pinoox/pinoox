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

namespace App\com_pinoox_installer\Controller;

use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Component\Migration\Migrator;
use Pinoox\Component\Package\AppManager;
use Pinoox\Model\UserModel;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppRouter;
use Pinoox\Portal\Config;
use Pinoox\Portal\Database\DB;

class ApiController extends Controller
{

    public function generateConfig($c): array
    {
        return [
            'host' => $c['host'] ?? null,
            'database' => $c['database'] ?? null,
            'username' => $c['username'] ?? null,
            'password' => $c['password'] ?? null,
            'prefix' => $c['prefix'] ?? null,
            'driver' => 'mysql',
            'port' => '3306',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_bin',
            'strict' => true,
            'engine' => 'InnoDB',
        ];
    }

    public function changeLang($lang): array
    {
        $lang = strtolower($lang);
        App::set('lang', $lang)
            ->save();
        return $this->getLang();
    }

    protected function getLang($lang = null): array
    {
        $lang = empty($lang) ? App::get('lang') : $lang;
        return [
            'direction' => in_array($lang, ['fa', 'ar']) ? 'rtl' : 'ltr',
            'lang' => [
                'install' => t('install'),
                'user' => t('user'),
                'language' => t('language'),
            ]
        ];
    }

    private function checkConnection($data): bool
    {
        DB::addConnection($this->generateConfig($data));
        DB::bootEloquent();

        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkDB(Request $request)
    {
        $data = $request->json('host,database,username,password,prefix', '', '!empty');

        if ($this->checkConnection($data)) {
            return $this->message('connect', true);
        }

        return $this->message('disconnect', false);
    }

    public function checkPrerequisites($type)
    {
        $status = false;
        switch ($type) {
            case 'php' :
                $status = (bool)version_compare(phpversion(), '8.1.0', '>=');
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
        return t('agreement');
    }

    public function setup(Request $request)
    {
        $validation = $request->validation([
            'user.fname' => 'required|min:3',
            'user.lname' => 'required|min:3',
            'user.email' => 'required|email',
            'user.username' => 'required|alpha_dash:ascii|min:3',
            'user.password' => 'required|min:6',
        ]);

        if ($validation->fails())
            return $this->message($validation->errors()->first(), false);

        $data = $validation->validate();
        $user = $data['user'];
        $db = $request->json->all('db');

        // Add database core
        if (!$this->insertTables($db, $user)) {
            return $this->message(t('install.err_insert_tables'), false);
        }

        // Set primary apps route
        $appRoutes = Config::name('app')->get();
        AppRouter::setData($appRoutes);

        // Install exists apps
        $this->installExistsApps();

        // Disable installer app
        App::set('enable', false)
            ->save();

        return $this->message('success', true);
    }

    private function insertTables($c, $u)
    {
        if (empty($c) || empty($u))
            return false;

        $data = $this->generateConfig($c);

        if (!$this->checkConnection($data)) return false;

        Config::name('~database')
            ->set('production', $data)
            ->set('development', $data)
            ->save();

        try {

            $initializer = new Migrator('pincore', 'init');
            $initializer->init();

            $migrator = new Migrator('pincore', 'run');
            $migrator->run();

        } catch (\Exception $e) {
            return false;
        }

        return UserModel::create([
            'app' => 'pincore',
            'fname' => $u['fname'],
            'lname' => $u['lname'],
            'username' => $u['username'],
            'password' => $u['password'],
            'email' => $u['email'],
        ]);
    }

    private function installExistsApps()
    {
        $packageInstaller = App::package();
        $langInstaller = App::get('lang');
        $apps = AppEngine::all();
        foreach ($apps as $appManager) {
            /**
             * @var AppManager $appManager
             */
            if ($appManager->package() === $packageInstaller)
                continue;

            // migrate
            $migrator = new Migrator($appManager->package(), 'run');
            $migrator->run();

            // default lang
            if ($appManager->lang()->existsLocale($langInstaller)) {
                $appManager->config()->set('lang', $langInstaller)->save();
            }
        }
    }

    private function message($result, $status)
    {
        return ["status" => $status, "result" => $result];
    }
}

