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


namespace pinoox\app\com_pinoox_manager\component;


use pinoox\component\app\AppProvider;
use pinoox\component\Cache;
use pinoox\component\Config;
use pinoox\component\File;
use pinoox\component\Router;
use pinoox\component\Service;
use pinoox\component\User;
use pinoox\component\Zip;
use pinoox\model\PinooxDatabase;
use pinoox\model\SessionModel;
use pinoox\model\TokenModel;
use pinoox\model\UserModel;

class Wizard
{
    private static $isApp = false;

    public static function installApp($file, $packageName)
    {
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
            self::runService($packageName, 'install');
        }

        File::remove_file($file);
    }

    private static function runService($packageName, $state = 'install')
    {
        self::setApp($packageName);
        Cache::app($packageName);
        Service::app($packageName);
        Service::run('app>' . $state);
    }

    private static function setApp($packageName)
    {
        if (self::$isApp) return;
        self::$isApp = true;
        Router::setApp($packageName);
        AppProvider::app($packageName);
    }

    public static function updateApp($file, $packageName, $linkApp, $versionCode, $versionName)
    {
        Zip::remove($file, [
            $packageName . '/config/',
            $packageName . '/cache/',
            $packageName . '/app.php',
            $packageName . '/app.db',
        ]);

        $appPath = path('~apps/');

        Zip::extract($file, $appPath);
        File::remove_file($file);


        self::setApp($packageName);
        AppProvider::set('version-code', $versionCode);
        AppProvider::set('version-name', $versionName);
        self::runService($packageName, 'update');
    }

    public static function deleteApp($packageName)
    {
        $appPath = path('~apps/' . $packageName);
        File::remove($appPath);

        //remove route
        self::removeRoutes($packageName);

        //remove database
        self::removeDatabase($packageName);

        self::runService($packageName, 'delete');
    }

    private static function removeRoutes($packageName)
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

    private static function removeDatabase($packageName)
    {
        PinooxDatabase::startTransaction();

        $tables = PinooxDatabase::getTables($packageName);
        $tables = implode(',', $tables);
        PinooxDatabase::$db->rawQuery("SET FOREIGN_KEY_CHECKS = 0");

        //delete all tables
        PinooxDatabase::$db->rawQuery("DROP TABLE IF EXISTS " . $tables);

        //delete all rows
        UserModel::delete_by_app($packageName);
        TokenModel::delete_by_app($packageName);
        SessionModel::delete_by_app($packageName);

        PinooxDatabase::$db->rawQuery("SET FOREIGN_KEY_CHECKS = 1");
        PinooxDatabase::commit();
    }
}