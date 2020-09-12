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


use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\app\AppProvider;
use pinoox\component\Cache;
use pinoox\component\Config;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\Router;
use pinoox\component\Service;
use pinoox\component\Url;
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
            $query = str_replace('{dbprefix}', $prefix . $packageName . '_', $query);
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
        $current = Router::getApp();
        self::setApp($packageName);
        Cache::app($packageName);
        Service::app($packageName);
        Service::run('app>' . $state);
        Router::setApp($current);
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
            $packageName . '/pinker/',
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
        if (!empty($tables))
            PinooxDatabase::$db->rawQuery("DROP TABLE IF EXISTS " . $tables);

        //delete all rows
        UserModel::delete_by_app($packageName);
        TokenModel::delete_by_app($packageName);
        SessionModel::delete_by_app($packageName);

        PinooxDatabase::$db->rawQuery("SET FOREIGN_KEY_CHECKS = 1");
        PinooxDatabase::commit();
    }

    public static function updateCore($file)
    {
        Zip::extract($file, path('~'));
        File::remove_file($file);
        Cache::clean('version');
        Cache::get('version');
        Config::reset('~pinoox');
        Service::run('~core>update');

        Cache::app('com_pinoox_manager');
        Service::app('com_pinoox_manager');
        Service::run('app>update');
    }

    public static function is_installed($package_name)
    {
        $app = AppModel::fetch_by_package_name($package_name);
        return !empty($app);
    }

    public static function is_downloaded($package_name)
    {
        $file = Dir::path('downloads>apps>' . $package_name . '.pin');
        return (!empty($file) && file_exists($file));
    }

    public static function get_downloaded($package_name)
    {
        return Dir::path('downloads>apps>' . $package_name . '.pin');
    }

    public static function is_installed_template($package_name, $uid)
    {
        $file = Dir::path("~apps>$package_name>theme>$uid");
        return (!empty($file) && file_exists($file));
    }

    public static function is_downloaded_template($uid)
    {
        $file = Dir::path("downloads>templates>$uid.pin");
        return (!empty($file) && file_exists($file));
    }

    public static function get_downloaded_template($uid)
    {
        return Dir::path("downloads>templates>$uid.pin");
    }

    public static function installTemplate($file, $packageName, $meta)
    {
        Zip::extract($file, path("~apps>$packageName>theme>" . $meta['name']));
        File::remove_file($file);
    }

    public static function deleteTemplate($packageName, $folderName)
    {
        $templatePath = path('~apps/' . $packageName . '>theme>' . $folderName);
        File::remove($templatePath);
    }

    public static function checkTemplateFolderName($packageName, $templateFolderName)
    {
        $file = path("~apps>$packageName>theme>" . $templateFolderName);
        return file_exists($file);
    }

    public static function pullDataPackage($pinFile)
    {
        $filename = File::fullname($pinFile);
        $name = File::name($pinFile);
        $dir = File::dir($pinFile) . DIRECTORY_SEPARATOR . $name;
        $configFile = $dir . DIRECTORY_SEPARATOR . 'app.php';

        if (!is_file($configFile)) {
            Zip::addEntries('app.php');
            Zip::extract($pinFile, $dir);
        }

        $app = new AppProvider($configFile);
        $iconPath = $app->icon;

        $icon = Url::file('resources/default.png');
        if (!empty($iconPath)) {
            $iconFile = Dir::path($dir . '>' . $app->icon);
            if (!is_file($iconFile)) {
                Zip::addEntries($app->icon);
                Zip::extract($pinFile, $dir);
            }

            if (is_file($iconFile))
                $icon = Url::file($dir . '>' . $app->icon);
        }

        return [
            'filename' => $filename,
            'package_name' => $app->packageName,
            'name' => $app->name,
            'description' => $app->description,
            'version' => $app->versionName,
            'version_code' => $app->versionCode,
            'developer' => $app->developer,
            'icon' => $icon,
        ];
    }

    public static function pullTemplateMeta($file)
    {
        $name = File::name($file);
        $dir = File::dir($file) . DIRECTORY_SEPARATOR . $name;
        if (Zip::extract($file, $dir)) {
            $meta = file_get_contents(Dir::path($dir . '>meta.json'));
            $meta = json_decode($meta, true);
            File::remove($dir);
            return $meta;
        }
        return null;
    }

}