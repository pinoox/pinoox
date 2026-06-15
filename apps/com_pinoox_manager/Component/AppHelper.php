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



use Pinoox\Component\Package\AppManifest;
use Pinoox\Portal\App\AppRouter;

use Pinoox\Portal\App\AppEngine;

use Pinoox\Portal\Lang;

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



            $iconMeta = AppIconPack::resolve($app->package(), $appConfig->get('icon'), $appConfig);
            $labels = AppManifest::labels($app->package());
            $locale = Lang::locale();

            $result[$app->package()] = [

                'package_name' => $app->package(),

                'hidden' => $isHidden,

                'dock' => $appConfig->get('dock'),

                'router' => $isRouter,

                'name' => AppManifest::displayName($app->package(), $locale),

                'description' => AppManifest::description($app->package(), $locale),

                'labels' => $labels,

                'version' => $appConfig->get('version-name'),

                'version_code' => $appConfig->get('version-code'),

                'developer' => $appConfig->get('developer'),

                'open' => $appConfig->get('open'),

                'sys_app' => $appConfig->get('sys-app'),

                'icon' => $iconMeta['url'],

                'icon_source' => $iconMeta['source'],

                'icon_pack_id' => $iconMeta['pack_id'],

                'icon_lucide' => $iconMeta['lucide'],

                'icon_colors' => $iconMeta['colors'],

                'icon_style' => $iconMeta['style'],

                'icon_category' => $iconMeta['category'],

                'routes' => AppRouter::getByPackage($app->package()),

                'build' => $appConfig->get('build'),

                'app_view' => self::appViewSettings($appConfig),

            ];

        }



        return $result;

    }



    public static function getOne($packageName)

    {

        $result = null;

        if (AppEngine::exists($packageName)) {

            $app = AppEngine::config($packageName);

            $iconMeta = AppIconPack::resolve($packageName, $app->get('icon'), $app);
            $locale = Lang::locale();



            $result = [

                'name' => AppManifest::displayName($packageName, $locale),

                'hidden' => $app->get('hidden'),

                'dock' => $app->get('dock'),

                'router' => $app->get('router'),

                'enable' => $app->get('enable'),

                'open' => $app->get('open'),

                'sys-app' => $app->get('sys-app'),

                'description' => AppManifest::description($packageName, $locale),

                'labels' => AppManifest::labels($packageName),

                'version' => $app->get('version-name'),

                'version_code' => $app->get('version-code'),

                'developer' => $app->get('developer'),

                'icon' => $iconMeta['url'],

                'icon_source' => $iconMeta['source'],

                'icon_pack_id' => $iconMeta['pack_id'],

                'icon_lucide' => $iconMeta['lucide'],

                'icon_colors' => $iconMeta['colors'],

                'icon_style' => $iconMeta['style'],

                'icon_category' => $iconMeta['category'],

                'build' => $app->get('build'),

                'app_view' => self::appViewSettings($app),

            ];

        }



        return $result;

    }

}


