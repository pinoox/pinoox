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

namespace App\com_pinoox_manager\Controller\api\v1;

use App\com_pinoox_manager\Component\Wizard;
use App\com_pinoox_manager\Component\AppHelper;
use Pinoox\Component\Dir;
use Pinoox\Component\File;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Lang;
use Pinoox\Component\Request as RequestData;
use Pinoox\Component\Response;
use Pinoox\Component\Uploader;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Path;

class AppController extends LoginConfiguration
{

    public function get($filter = null)
    {
        switch ($filter) {
            case 'installed':
            {
                $result = AppHelper::fetch_all(false);
                break;
            }
            case 'systems':
            {
                $result = AppHelper::fetch_all(true);
                break;
            }
            default:
            {
                $result = AppHelper::fetch_all(null, true);
            }
        }

        return $result;
    }

    public function getConfig($packageName)
    {
        $config = AppHelper::fetch_by_package_name($packageName);
        return $config;
    }

    public function setConfig($packageName, $key)
    {
        $config = RequestData::inputOne('config');

        if ($key == 'dock')
            $config = !$config;
        if ($key == 'router')
            $config = $config === 'multiple' ? 'single' : 'multiple';

        if (!is_null($config)) {
            AppEngine::config($packageName)
            ->set($key, $config)
            ->save();
            return $this->message($config, true);
        } else {
            return $this->message(null, false);
        }
    }

    public function install($packageName)
    {
        if (empty($packageName))
            return $this->message(t('manager.request_install_app_not_valid'), false);

        $pinFile = Wizard::getDownloaded($packageName);
        if (!is_file($pinFile))
            return $this->message(t('manager.request_install_app_not_valid'), false);

        if (Wizard::installApp($pinFile)) {
            return $this->message(t('manager.done_successfully'), true);
        } else {
            $message = Wizard::getMessage();
            if (empty($message))
                return $this->message(t('manager.request_install_app_not_valid'), false);
            else
                return $this->message($message, false);
        }
    }

    public function installPackage($filename)
    {
        if (empty($filename))
            return $this->message(t('manager.request_install_app_not_valid'), false);

        $pinFile = Path::get(self::manualPath . $filename);
        if (!is_file($pinFile))
            return $this->message(t('manager.request_install_app_not_valid'), false);
        if (Wizard::installApp($pinFile)) {
            return $this->message(t('manager.done_successfully'), true);
        } else {
            $message = Wizard::getMessage();
            if (empty($message))
                return $this->message(t('manager.request_install_app_not_valid'), false);
            else
                return $this->message($message, false);
        }
    }

    public function updatePackage($filename)
    {
        if (empty($filename))
            return $this->message(t('manager.request_update_app_not_valid'), false);

        $pinFile = path(self::manualPath . $filename);
        if (!is_file($pinFile))
            return $this->message(t('manager.request_update_app_not_valid'), false);
        if (Wizard::updateApp($pinFile)) {
            return $this->message(t('manager.update_successfully'), true);
        } else {
            $message = Wizard::getMessage();
            if (empty($message))
                return $this->message(t('manager.request_update_app_not_valid'), false);
            else
                return $this->message($message, false);
        }
    }

    public function update($packageName)
    {
        if (empty($packageName))
            return $this->message(t('manager.request_update_app_not_valid'), false);

        $pinFile = Wizard::getDownloaded($packageName);
        if (!is_file($pinFile))
            return $this->message(t('manager.request_update_app_not_valid'), false);

        if (Wizard::updateApp($pinFile)) {
            return $this->message(t('manager.update_successfully'), true);
        } else {
            $message = Wizard::getMessage();
            if (empty($message))
                return $this->message(t('manager.request_update_app_not_valid'), false);
            else
                return $this->message($message, false);
        }
    }

    public function remove($packageName)
    {
        Wizard::deleteApp($packageName);
        return $this->message(t('manager.done_successfully'), true);
    }

    public function files()
    {
        $path = path(self::manualPath);
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
        return $this->message($files);
    }

    public function deleteFile(Request $request)
    {
        $filename = $request->getPayload()->get('filename');

        if (empty($filename))
            return $this->message(t('manager.error_happened'), false);

        $pinFile = path(self::manualPath . $filename);
        if (!is_file($pinFile))
            return $this->message(t('manager.error_happened'), false);

        Wizard::deletePackageFile($pinFile);
        return $this->message(t('manager.delete_successfully'), true);
    }

    public function filesUpload()
    {
        if (RequestData::isFile('files')) {
            $path = Path::get(self::manualPath);
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
                return $this->message(Lang::get('manager.file_uploaded_correctly'), true);
            } else if ($lengthUploaded === $length) {
                return $this->message(Lang::get('manager.files_uploaded_correctly'), true);
            } else {
                return $this->message([
                    'message' => Lang::replace('manager.some_files_uploaded_correctly', $length, $lengthUploaded),
                    'errs' => $errs
                ], false);
            }
        }
    }
}
