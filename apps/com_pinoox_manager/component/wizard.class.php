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
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\Lang;
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
    private static $message = null;

    public static function installApp($pinFile)
    {
        $data = self::pullDataPackage($pinFile);

        if (!self::isValidNamePackage($data['package_name']))
            return false;

        if (!self::checkVersion($data))
            return false;

        $appPath = path('~apps/' . $data['package_name'] . '/');
        Zip::extract($pinFile, $appPath);

        //check database
        $appDB = path('~apps/' . $data['package_name'] . '/app.db');

        self::runQuery($appDB, $data['package_name']);
        self::changeLang($data['package_name']);
        self::runService($data['package_name'], 'install');
        self::setApp('com_pinoox_manager', true);
        self::deletePackageFile($pinFile);

        return true;
    }

    public static function pullDataPackage($pinFile)
    {
        $filename = File::fullname($pinFile);
        $size = File::size($pinFile);
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
            'type' => 'app',
            'filename' => $filename,
            'package_name' => $app->packageName,
            'app' => $app->packageName,
            'name' => $app->name,
            'description' => $app->description,
            'version' => $app->versionName,
            'version_code' => $app->versionCode,
            'developer' => $app->developer,
            'path_icon' => $app->icon,
            'icon' => $icon,
            'size' => File::print_size($size, 1),
        ];
    }

    public static function isValidNamePackage($packageName)
    {
        if (!empty($packageName)) {
            $parts = explode('_', $packageName);
            return count($parts) >= 2;
        }
        return false;
    }

    public static function checkVersion($data)
    {
        $packageName = $data['package_name'];
        $versionCode = @$data['version_code'];

        if (!Router::existApp($packageName))
            return true;

        $app = new AppProvider($packageName);
        $versionCodeApp = $app->versionCode;

        if ($versionCodeApp == $versionCode) {
            self::$message = Lang::get('manager.version_already_installed');
            return false;
        } else if ($versionCodeApp > $versionCode) {
            self::$message = Lang::get('manager.newer_version_installed');
            return false;
        }

        return true;
    }

    public static function runQuery($appDB, $package_name, $isRemoveFile = true, $isCopyUser = true)
    {
        if (is_file($appDB)) {
            $prefix = Config::get('~database.prefix');
            $query = file_get_contents($appDB);
            $query = str_replace('{dbprefix}', $prefix . $package_name . '_', $query);
            $queryArr = explode(';', $query);

            PinooxDatabase::$db->startTransaction();
            foreach ($queryArr as $q) {
                if (empty($q)) continue;
                PinooxDatabase::$db->mysqli()->query($q);
            }

            //copy new user
            if ($isCopyUser)
                UserModel::copy(User::get('user_id'), $package_name);

            PinooxDatabase::$db->commit();

            if ($isRemoveFile)
                File::remove_file($appDB);

            return true;
        }
        return false;
    }

    public static function changeLang($package_name)
    {
        $lang = Lang::current();
        if (!Lang::exists($lang, $package_name))
            return false;
        self::setApp($package_name);
        AppProvider::set('lang', $lang);
        AppProvider::save();
        return true;
    }

    private static function setApp($packageName, $isAgain = false)
    {
        if (self::$isApp && !$isAgain) return;
        self::$isApp = true;
        Router::setApp($packageName);
        AppProvider::app($packageName);
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

    public static function deletePackageFile($pinFile)
    {
        $name = File::name($pinFile);
        $dir = File::dir($pinFile) . DIRECTORY_SEPARATOR . $name;
        File::remove_file($pinFile);
        File::remove($dir);
    }

    public static function updateApp($pinFile)
    {
        $data = self::pullDataPackage($pinFile);

        if (!self::isValidNamePackage($data['package_name'])) {
            self::deletePackageFile($pinFile);
            return false;
        }

        if (!self::checkVersion($data))
            return false;

        Zip::remove($pinFile, [
            'pinker/',
        ]);

        $appPath = path('~apps/' . $data['package_name'] . '/');

        Zip::extract($pinFile, $appPath);
        File::remove_file($pinFile);


        self::setApp($data['package_name']);
        AppProvider::set('version-code', $data['version_code']);
        AppProvider::set('version-name', $data['version']);
        AppProvider::set('name', $data['name']);
        AppProvider::set('developer', $data['developer']);
        AppProvider::set('description', $data['description']);
        AppProvider::set('icon', $data['path_icon']);
        AppProvider::save();
        self::runService($data['package_name'], 'update');

        self::setApp('com_pinoox_manager', true);
        self::deletePackageFile($pinFile);

        return true;

    }

    public static function getMessage()
    {
        $message = self::$message;
        self::$message = null;
        return $message;
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
        Config::bake('~pinoox');
        Service::run('~core>update');

        Cache::app('com_pinoox_manager');
        Service::app('com_pinoox_manager');
        Service::run('app>update');
    }

    public static function app_state($package_name)
    {
        if (self::is_installed($package_name))
            $state = 'installed';
        else if (self::is_downloaded($package_name))
            $state = 'install';
        else
            $state = 'download';

        return $state;
    }

    public static function is_installed($package_name)
    {
        return Router::existApp($package_name);
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

    public static function template_state($package_name, $uid)
    {
        if (self::is_installed_template($package_name, $uid))
            $state = 'installed';
        else if (self::is_downloaded_template($uid))
            $state = 'install';
        else
            $state = 'download';

        return $state;
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
        if (Zip::extract($file, path("~apps>$packageName>theme>" . $meta['name']))) {
            File::remove_file($file);
            return true;
        }

        return false;
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

    public static function pullTemplateMeta($pinFile)
    {
        $filename = File::fullname($pinFile);
        $size = File::size($pinFile);
        $name = File::name($pinFile);
        $dir = File::dir($pinFile) . DIRECTORY_SEPARATOR . $name;
        $metaFile = $dir . DIRECTORY_SEPARATOR . 'meta.json';

        if (!is_file($metaFile)) {
            Zip::addEntries('meta.json');
            Zip::extract($pinFile, $dir);
        }

        $meta = json_decode(file_get_contents($metaFile), true);
        $coverPath = @$meta['cover'];

        $cover = Url::file('resources/theme.jpg');
        if (!empty($coverPath)) {
            $coverFile = Dir::path($dir . '>' . $coverPath);
            if (!is_file($coverFile)) {
                Zip::addEntries($coverPath);
                Zip::extract($pinFile, $dir);
            }

            if (is_file($coverFile))
                $cover = Url::file($dir . '>' . $coverPath);
        }

        if (empty($meta['title'])) {
            $title = null;
        } else if (empty($meta['title'][Lang::current()])) {
            $title = array_values($meta['title'])[0];
        } else {
            $title = $meta['title'][Lang::current()];
        }

        return [
            'type' => 'theme',
            'filename' => $filename,
            'template_name' => $title,
            'app' => @$meta['app'],
            'name' => @$meta['name'],
            'title' => @$meta['title'],
            'description' => @$meta['description'],
            'version' => @$meta['version'],
            'version_code' => @$meta['app_version'],
            'developer' => @$meta['developer'],
            'path_cover' => @$meta['cover'],
            'cover' => $cover,
            'size' => File::print_size($size, 1),
        ];
    }
}