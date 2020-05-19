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

use pinoox\app\com_pinoox_manager\component\Wizard;
use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\app\AppProvider;
use pinoox\component\Cache;
use pinoox\component\Dir;
use pinoox\component\Download;
use pinoox\component\File;
use pinoox\component\Request;
use pinoox\component\Response;
use pinoox\component\Router;
use pinoox\component\Service;
use pinoox\component\Validation;
use pinoox\component\Zip;

class AppController extends MasterConfiguration
{
    public function get($filter = null)
    {
        switch ($filter) {
            case 'installed':
                {
                    $result = AppModel::fetch_all(false);
                    break;
                }
            case 'ready_install':
                {
                    $result = AppModel::fetch_all_ready_to_install();
                    break;
                }
            case 'systems':
                {
                    $result = AppModel::fetch_all(true);
                    break;
                }
            default:
                {
                    $result = AppModel::fetch_all(null, true);
                }
        }

        Response::json($result);
    }

    public function getConfig($packageName)
    {
        $config = AppModel::fetch_by_package_name($packageName);
        Response::json($config);
    }

    public function setConfig($packageName, $key)
    {
        $config = Request::inputOne('config');

        if ($key == 'hidden')
            $config = !$config? true : false;
        if ($key == 'router')
            $config = $config === 'multiple' ? 'single' : 'multiple';
        $currentApp = AppProvider::app();
        if (!is_null($config)) {
            AppProvider::app($packageName);
            AppProvider::set($key, $config);
            AppProvider::save();
            Response::json($config, true);
        } else {
            Response::json(null, false);
        }
        AppProvider::app($currentApp);


    }

    public function install($packageName)
    {
        if (empty($packageName))
            Response::json(rlang('manager.request_install_app_not_valid'), false);

        $file = Dir::path('downloads>apps>' . $packageName . '.pin');
        Wizard::installApp($file, $packageName);
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

    public function remove($packageName)
    {
        Wizard::deleteApp($packageName);
        Response::json(rlang('manager.done_successfully'), true);
    }

    public function readyInstallCount()
    {
        $apps = AppModel::fetch_all_ready_to_install();
        Response::json(count($apps));
    }
}
