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

namespace pinoox\app\com_pinoox_manager\model;

use pinoox\app\com_pinoox_manager\component\Wizard;
use pinoox\component\app\AppProvider;
use pinoox\component\Config;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\Router;
use pinoox\component\Url;
use pinoox\model\PinooxDatabase;

class AppModel extends PinooxDatabase
{
    /**
     * @param null|boolean $sysApp null: return all installed and system apps | true: return all system apps | false: return all installed app
     * @param bool $isCheckHidden
     * @param bool $isCheckRouter
     * @return array
     */
    public static function fetch_all($sysApp = null, $isCheckHidden = false, $isCheckRouter = false)
    {
        $path = Dir::path('~apps/');
        $folders = File::get_dir_folders($path);
        $icon_default = Url::file('resources/default.png');
        $app = Router::getApp();

        $result = [];
        foreach ($folders as $folder) {
            $package_key = basename($folder);

            if (!Router::existApp($package_key))
                continue;
            Router::setApp($package_key);
            AppProvider::app($package_key);

            $isEnable = AppProvider::get('enable');
            if (!$isEnable)
                continue;

            $isHidden = AppProvider::get('hidden');
            if ($isHidden)
                continue;

            $isRouter = AppProvider::get('router');
            if ($isCheckRouter && !$isRouter)
                continue;

            if (!is_null($sysApp)) {
                $sysAppState = AppProvider::get('sys-app');
                if ($sysApp && !$sysAppState) {
                    continue;
                } else if (!$sysApp && $sysAppState) {
                    continue;
                }
            }

            $result[$package_key] = [
                'package_name' => $package_key,
                'hidden' => $isHidden,
                'dock' => AppProvider::get('dock'),
                'router' => $isRouter,
                'name' => AppProvider::get('name'),
                'description' => AppProvider::get('description'),
                'version' => AppProvider::get('version-name'),
                'version_code' => AppProvider::get('version-code'),
                'developer' => AppProvider::get('developer'),
                'open' => AppProvider::get('open'),
                'sys_app' => AppProvider::get('sys-app'),
                'icon' => Url::check(Url::file(AppProvider::get('icon'), $package_key), $icon_default),
                'routes' => self::fetch_all_aliases_by_package_name($package_key)
            ];
        }

        AppProvider::app('~');
        Router::setApp($app);
        return $result;
    }

    public static function fetch_all_aliases_by_package_name($packageName)
    {
        $routes = Config::get('~app');
        $aliases = [];
        foreach ($routes as $alias => $package) {
            if ($package == $packageName) {
                $aliases[] = $alias;
            }
        }
        return $aliases;
    }

    public static function fetch_by_package_name($packageName)
    {
        $icon_default = Url::file('resources/default.png');
        $app = Router::getApp();

        Router::setApp($packageName);
        AppProvider::app($packageName);
        $result = null;
        if (Router::existApp($packageName)) {
            $result = [
                'name' => AppProvider::get('name'),
                'hidden' => AppProvider::get('hidden'),
                'dock' => AppProvider::get('dock'),
                'router' => AppProvider::get('router'),
                'enable' => AppProvider::get('enable'),
                'open' => AppProvider::get('open'),
                'sys-app' => AppProvider::get('sys-app'),
                'description' => AppProvider::get('description'),
                'version' => AppProvider::get('version-name'),
                'version_code' => AppProvider::get('version-code'),
                'developer' => AppProvider::get('developer'),
                'icon' => Url::check(Url::file(AppProvider::get('icon'), $packageName), $icon_default),
            ];
        }
        AppProvider::app('~');
        Router::setApp($app);

        return $result;
    }

    public static function fetch_all_downloads()
    {
        $folders = File::get_dir_folders(Dir::path('downloads>apps'));
        if (!empty($folders) && isset($folders[0])) {
            $folder = $folders[0];
            $files = File::get_files_by_pattern($folder, '*.pin');
            $result = [];

            foreach ($files as $file)
            {
                $data = Wizard::pullDataPackage($file);
                if (!Wizard::isValidNamePackage($data['package_name']) || !Config::getLinear('market',$data['package_name']))
                {
                    Wizard::deletePackageFile($file);
                    Config::remove('market.' . $data['package_name']);
                    Config::save('market');
                    continue;
                }
                $data['market'] = Config::get('market.' . $data['package_name']);
                $result[] = $data;
            }

            return $result;
        }

    }


}