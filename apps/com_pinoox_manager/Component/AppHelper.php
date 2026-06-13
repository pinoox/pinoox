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

namespace App\com_pinoox_manager\Component;

use Pinoox\Component\Dir;
use Pinoox\Component\File;
use Pinoox\Portal\App\AppRouter;
use Pinoox\Portal\Path;
use Pinoox\Portal\Url;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Config;
use Pinoox\Component\Package\AppManager;

class AppHelper
{
    public static function appViewSettings($appConfig): array
    {
        $appView = $appConfig->get('app-view');

        if (!is_array($appView)) {
            $appView = [];
        }

        return [
            'address_bar' => array_key_exists('address-bar', $appView)
                ? (bool) $appView['address-bar']
                : true,
        ];
    }

    public static function getAll(null|bool $sysApp = null,bool $isCheckHidden = false,bool $isCheckRouter = false)
    {
        $icon_default = Url::asset('resources/default.png');
        $apps = AppEngine::all();
        $result = [];
        /**
         * @var AppManager $app
         */

        foreach ($apps as $app) {
            if (!$app->exists())
                continue;

            $appConfig = $app->config();

            $isEnable = $appConfig->get('enable');
            if (!$isEnable)
                continue;

            $isHidden = $appConfig->get('hidden');
            if (!$isCheckHidden && $isHidden)
                continue;

            $isRouter = $appConfig->get('router');
            if ($isCheckRouter && !$isRouter)
                continue;

            if (!is_null($sysApp)) {
                $sysAppState = $appConfig->get(('sys-app'));
                if ($sysApp && !$sysAppState) {
                    continue;
                } else if (!$sysApp && $sysAppState) {
                    continue;
                }
            }

            $iconRef = $appConfig->get('icon');
            $icon = is_string($iconRef) && $iconRef !== ''
                ? Url::reference($iconRef, $app->package())
                : $icon_default;
            $result[$app->package()] = [
                'package_name' => $app->package(),
                'hidden' => $isHidden,
                'dock' => $appConfig->get('dock'),
                'router' => $isRouter,
                'name' => $appConfig->get('name'),
                'description' => $appConfig->get('description'),
                'version' => $appConfig->get('version-name'),
                'version_code' => $appConfig->get('version-code'),
                'developer' => $appConfig->get('developer'),
                'open' => $appConfig->get('open'),
                'sys_app' => $appConfig->get('sys-app'),
                'icon' => Url::check($icon, $icon_default),
                'routes' => AppRouter::getByPackage($app->package()),
                'build' => $appConfig->get('build'),
                'app_view' => self::appViewSettings($appConfig),
            ];
        }

        return $result;
    }

    public static function getOne($packageName)
    {
        $icon_default = Url::asset('resources/default.png');
        $result = null;
        if (AppEngine::exists($packageName)) {
            $app = AppEngine::config($packageName);

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
                'icon' => Url::check(Url::asset($app->get('icon'), $packageName), $icon_default),
                'build' => $app->get('build'),
                'app_view' => self::appViewSettings($app),
            ];
        }

        return $result;
    }
}

