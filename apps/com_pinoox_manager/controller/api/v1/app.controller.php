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
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\Lang;
use pinoox\component\Request;
use pinoox\component\Response;
use pinoox\component\Uploader;

class AppController extends LoginConfiguration
{
    public function get($filter = null)
    {
        switch ($filter) {
            case 'installed':
            {
                $result = AppModel::fetch_all(false);
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

        if ($key == 'dock')
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

        $pinFile = Wizard::get_downloaded($packageName);
        if (!is_file($pinFile))
            Response::json(rlang('manager.request_install_app_not_valid'), false);

        if (Wizard::installApp($pinFile)) {
            Response::json(rlang('manager.done_successfully'), true);
        } else {
            $message = Wizard::getMessage();
            if (empty($message))
                Response::json(rlang('manager.request_install_app_not_valid'), false);
            else
                Response::json($message, false);
        }
    }

    public function installPackage($filename)
    {
        if (empty($filename))
            Response::json(rlang('manager.request_install_app_not_valid'), false);

        $pinFile = Dir::path(self::manualPath . $filename);
        if (!is_file($pinFile))
            Response::json(rlang('manager.request_install_app_not_valid'), false);
        if (Wizard::installApp($pinFile)) {
            Response::json(rlang('manager.done_successfully'), true);
        } else {
            $message = Wizard::getMessage();
            if (empty($message))
                Response::json(rlang('manager.request_install_app_not_valid'), false);
            else
                Response::json($message, false);
        }
    }

    public function updatePackage($filename)
    {
        if (empty($filename))
            Response::json(rlang('manager.request_update_app_not_valid'), false);

        $pinFile = Dir::path(self::manualPath . $filename);
        if (!is_file($pinFile))
            Response::json(rlang('manager.request_update_app_not_valid'), false);
        if (Wizard::updateApp($pinFile)) {
            Response::json(rlang('manager.update_successfully'), true);
        } else {
            $message = Wizard::getMessage();
            if (empty($message))
                Response::json(rlang('manager.request_update_app_not_valid'), false);
            else
                Response::json($message, false);
        }
    }

    public function update($packageName)
    {
        if (empty($packageName))
            Response::json(rlang('manager.request_update_app_not_valid'), false);

        $pinFile = Wizard::get_downloaded($packageName);
        if (!is_file($pinFile))
            Response::json(rlang('manager.request_update_app_not_valid'), false);

        if (Wizard::updateApp($pinFile)) {
            Response::json(rlang('manager.update_successfully'), true);
        } else {
            $message = Wizard::getMessage();
            if (empty($message))
                Response::json(rlang('manager.request_update_app_not_valid'), false);
            else
                Response::json($message, false);
        }
    }

    public function remove($packageName)
    {
        Wizard::deleteApp($packageName);
        Response::json(rlang('manager.done_successfully'), true);
    }

    public function files()
    {
        $path = Dir::path(self::manualPath);
        $files = File::get_files_by_pattern($path, '*.pin');
        $files = array_map(function ($file) {
            $data = Wizard::pullDataPackage($file);
            if (!Wizard::isValidNamePackage($data['package_name'])) {
                $data = Wizard::pullTemplateMeta($file);
                if (!Wizard::isValidNamePackage($data['app'])) {
                    Wizard::deletePackageFile($file);
                    return false;
                }
            }

            return $data;
        }, $files);
        $files = array_filter($files);
        Response::json($files);
    }

    public function deleteFile()
    {
        $filename = Request::inputOne('filename', null, '!empty');

        if (empty($filename))
            Response::json(Lang::get('manager.error_happened'), false);

        $pinFile = Dir::path(self::manualPath . $filename);
        if (!is_file($pinFile))
            Response::json(Lang::get('manager.error_happened'), false);

        Wizard::deletePackageFile($pinFile);
        Response::json(Lang::get('manager.delete_successfully'), true);
    }

    public function filesUpload()
    {
        if (Request::isFile('files')) {
            $path = Dir::path(self::manualPath);
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
