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

use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\Router;
use pinoox\component\Url;
use pinoox\portal\app\App;
use pinoox\portal\app\AppEngine;
use pinoox\portal\Config;

class AppHelper
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
        $app = App::package();

        $result = [];
        foreach ($folders as $folder) {
            $package_key = basename($folder);

            if (!AppEngine::exists($package_key))
                continue;

            $app = AppEngine::config($package_key);

            $isEnable = $app->get('enable');
            if (!$isEnable)
                continue;

            $isHidden = $app->get('hidden');
            if (!$isCheckHidden && $isHidden)
                continue;

            $isRouter = $app->get('router');
            if ($isCheckRouter && !$isRouter)
                continue;

            if (!is_null($sysApp)) {
                $sysAppState = $app->get(('sys-app'));
                if ($sysApp && !$sysAppState) {
                    continue;
                } else if (!$sysApp && $sysAppState) {
                    continue;
                }
            }

            $result[$package_key] = [
                'package_name' => $package_key,
                'hidden' => $isHidden,
                'dock' => $app->get('dock'),
                'router' => $isRouter,
                'name' => $app->get('name'),
                'description' => $app->get('description'),
                'version' => $app->get('version-name'),
                'version_code' => $app->get('version-code'),
                'developer' => $app->get('developer'),
                'open' => $app->get('open'),
                'sys_app' => $app->get('sys-app'),
                'icon' => Url::check(Url::file($app->get('icon'), $package_key), $icon_default),
                'routes' => self::fetch_all_aliases_by_package_name($package_key),
                'build' => $app->get('build')
            ];
        }

        return $result;
    }

    public static function fetch_all_aliases_by_package_name($packageName)
    {
        $routes = Config::name('~app')->get();
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
        $app = AppEngine::config($packageName);
        $result = null;
        if (Router::existApp($packageName)) {
            $result = [
                'name' => $app->get('name'),
                'hidden' => $app->get('hidden'),
                'dock' => $app->get('dock'),
                'router' => $app->get('router'),
                'enable' => $app->get('enable'),
                'open' => $app->get('open'),
                'sys-app' => $app->get('sys-app'),
                'description' => $app->get('description'),
                'version' => $app->get('version-name'),
                'version_code' => $app->get('version-code'),
                'developer' => $app->get('developer'),
                'icon' => Url::check(Url::file($app->get('icon'), $packageName), $icon_default),
                'build' => $app->get('build')
            ];
        }

        return $result;
    }

    public static function fetch_all_downloads()
    {
        $folders = File::get_dir_folders(Dir::path('downloads>apps'));
        if (!empty($folders) && isset($folders[0])) {
            $folder = $folders[0];
            $files = File::get_files_by_pattern($folder, '*.pin');
            $result = [];

            foreach ($files as $file) {
                $data = Wizard::pullDataPackage($file);
                if (!Wizard::isValidNamePackage($data['package_name']) || !Config::getLinear('market', $data['package_name'])) {
                    Wizard::deletePackageFile($file);
                    Config::name('market')->remove($data['package_name'])->save();
                    continue;
                }
                $data['market'] = Config::name('market')->get($data['package_name']);
                $result[] = $data;
            }

            return $result;
        }

    }


}
