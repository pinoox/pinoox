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
use pinoox\component\Lang;
use pinoox\component\Request;
use pinoox\component\Response;
use pinoox\component\Router;
use pinoox\component\Service;
use pinoox\component\Uploader;
use pinoox\component\Zip;

class AppController extends MasterConfiguration
{
    const manuelPath = 'downloads/packages/manuel/';

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

        if ( $key == 'dock')
            $config = !$config;
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

        $file = Wizard::get_downloaded($packageName);
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

            Wizard::updateApp($file);
            $message = rlang('manager.update_successfully');
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

    public function files()
    {
        $path = Dir::path(self::manuelPath);
        $files = File::get_files_by_pattern($path, '*.pin');
        $files = array_map(function ($file) {
            return Wizard::pullDataPackage($file);
        }, $files);
        Response::json($files);
    }

    public function deleteFile()
    {
        $filename = Request::inputOne('filename', null, '!empty');

        if (empty($filename))
            Response::json(Lang::get('manager.error_happened'), false);

        $pinFile = Dir::path(self::manuelPath . $filename);
        if (!is_file($pinFile))
            Response::json(Lang::get('manager.error_happened'), false);

        Wizard::deletePackageFile($pinFile);
        Response::json(Lang::get('manager.delete_successfully'), true);
    }

    public function filesUpload()
    {
        if (Request::isFile('files')) {
            $path = Dir::path(self::manuelPath);
            $up = Uploader::init('files', $path)->allowedTypes("pin", '*')
                ->changeName('none')
                ->finish();

            $result = $up->result();
            $length = count($result);
            $errs = $up->error(true);
            $uploaded = array_filter($result, function ($row) {
                return $row ? true : false;
            });

            $lengthUploaded = count($uploaded);

            if ($length === 1 && $lengthUploaded === $length) {
                Response::json(Lang::get('manager.file_uploaded_correctly'), true);
            } else if ($lengthUploaded === $length) {
                Response::json(Lang::get('manager.files_uploaded_correctly'), true);
            } else {
                Response::json([
                    'message' => Lang::replace('manager.some_files_uploaded_correctly', $length, $lengthUploaded),
                    'errs' => $errs
                ], false);
            }
        }
    }
}
