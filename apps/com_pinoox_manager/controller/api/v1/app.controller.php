<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\app\com_pinoox_manager\controller\api\v1;

use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\app\AppProvider;
use pinoox\component\Cache;
use pinoox\component\Config;
use pinoox\component\Download;
use pinoox\component\File;
use pinoox\component\HelperHeader;
use pinoox\component\Lang;
use pinoox\component\Request;
use pinoox\component\Response;
use pinoox\component\Router;
use pinoox\component\Service;
use pinoox\component\User;
use pinoox\component\Validation;
use pinoox\component\Zip;
use pinoox\model\PinooxDatabase;
use pinoox\model\UserModel;

class AppController extends MasterConfiguration
{
    public function get()
    {
        $result = AppModel::fetch_all();
        Response::json($result);
    }

    public function remove()
    {
        $packageName = Request::inputOne('packageName');
        $app = AppModel::fetch_by_package_name($packageName);
        if (!empty($app)) {

            $appPath = path('~apps/' . $packageName);
            File::removedir($appPath);

            //remove route
            $this->removeRoutes($packageName);

            //remove paper database
            $this->removeDatabasePaper($packageName);


            Response::json(rlang('manager.delete_successfully'), true);
        }
        Response::json(rlang('manager.error_happened'), false);
    }

    private function removeRoutes($packageName)
    {
        $routes = Config::get('~app');
        foreach ($routes as $alias => $package) {
            if ($package == $packageName && $alias != '*') {
                unset($routes[$alias]);
            }
        }
        Config::set('~app', $routes);
        Config::save('~app');
    }

    private function removeDatabasePaper($packageName)
    {
        if ($packageName == 'com_pinoox_paper') {

            PinooxDatabase::startTransaction();

            //delete all tables

            $pfx = Config::get('~database.prefix');

            PinooxDatabase::$db->rawQuery("DROP TABLE IF EXISTS  " . $pfx . "article");
            PinooxDatabase::$db->rawQuery("DROP TABLE IF EXISTS  " . $pfx . "tag");
            PinooxDatabase::$db->rawQuery("DROP TABLE IF EXISTS  " . $pfx . "article_tag");
            PinooxDatabase::$db->rawQuery("DROP TABLE IF EXISTS  " . $pfx . "menu");
            PinooxDatabase::$db->rawQuery("DROP TABLE IF EXISTS  " . $pfx . "settings");
            PinooxDatabase::$db->rawQuery("DROP TABLE IF EXISTS  " . $pfx . "page");
            PinooxDatabase::$db->rawQuery("DROP TABLE IF EXISTS  " . $pfx . "comment");
            PinooxDatabase::$db->rawQuery("DROP TABLE IF EXISTS  " . $pfx . "contact");

            //remove user
            UserModel::delete_by_app($packageName);
            PinooxDatabase::commit();
        }
    }

    public function download()
    {
        $data = Request::input('packageName,downloadLink');
        $messages = [
            'packageName' => rlang('manager.request_install_app_not_valid'),
            'downloadLink' => rlang('manager.request_install_app_not_valid'),];
        $valid = Validation::check($data, [
            'packageName' => ['required'],
            'downloadLink' => ['required'],
        ], $messages);

        if ($valid->isFail())
            Response::json($valid->first(), false);

        $app = AppModel::fetch_by_package_name($data['packageName']);
        if (!empty($app))
            Response::json(rlang('manager.currently_installed'), false);

        $file = path('temp/' . $data['packageName'] . '.pin');

        Download::fetch($data['downloadLink'], $file)->process();
        Response::json(rlang('manager.download_successfully'), true);
    }

    public function install()
    {
        $packageName = Request::inputOne('packageName');

        if (empty($packageName))
            Response::json(rlang('manager.request_install_app_not_valid'), false);

        $app = AppModel::fetch_by_package_name($packageName);
        $file = path('temp/' . $packageName . '.pin');

        if(!is_file($file) || !empty($app))
            Response::json(rlang('manager.currently_installed'), false);

        Zip::extract($file, path('~apps/'));

        //check database
        $appDB = path('~apps/' . $packageName . '/app.db');
        if (is_file($appDB)) {
            $prefix = Config::get('~database.prefix');
            $query = file_get_contents($appDB);
            $query = str_replace('{dbprefix}', $prefix, $query);
            $queryArr = explode(';', $query);

            PinooxDatabase::$db->startTransaction();
            foreach ($queryArr as $q) {
                if (empty($q)) continue;
                PinooxDatabase::$db->mysqli()->query($q);
            }

            //copy new user
            UserModel::copy(User::get('user_id'), $packageName);

            PinooxDatabase::$db->commit();
            File::remove_file($appDB);
        }
        File::remove_file($file);

        Response::json(rlang('manager.done_successfully'), true);
    }

    public function update()
    {
        $data = Request::input('packageName,downloadLink,versionCode,versionName');

        if (empty($data['packageName']) || empty($data['downloadLink']))
            Response::json(rlang('manager.request_update_app_not_valid'), false);

        $app = AppModel::fetch_by_package_name($data['packageName']);
        if (!empty($app)) {

            if ($app['version_code'] >= $data['versionCode'])
                Response::json(rlang('manager.request_update_app_not_valid'), false);

            $file = path('temp/' . $data['packageName'] . '.pin');
            Download::fetch($data['downloadLink'], $file)->process();

            Zip::remove($file, [
                $data['packageName'] . '/config/',
                $data['packageName'] . '/cache/',
                $data['packageName'] . '/app.php',
                $data['packageName'] . '/app.db',
            ]);

            $appPath = path('~apps/');

            Zip::extract($file, $appPath);
            File::remove_file($file);

            $message = rlang('manager.update_successfully');
            Router::setApp($data['packageName']);
            AppProvider::app($data['packageName']);
            AppProvider::set('version-code', $data['versionCode']);
            AppProvider::set('version-name', $data['versionName']);
            Cache::app($data['packageName']);
            Service::app($data['packageName']);
            Service::run('app>update');
            AppProvider::save();
            Response::json($message, true);
        }


        Response::json(rlang('manager.error_happened'), true);
    }

    public function market($keyword = null)
    {
        $data = Download::fetch('https://www.pinoox.com/api/v1/market/' . $keyword)->process();
        HelperHeader::contentType('application/json', 'UTF-8');
        echo $data;
    }
}
