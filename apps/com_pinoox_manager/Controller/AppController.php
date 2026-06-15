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

namespace App\com_pinoox_manager\Controller;

use App\com_pinoox_manager\Component\AppHelper;
use App\com_pinoox_manager\Component\AppIconPack;
use App\com_pinoox_manager\Component\Wizard;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\File;
use Pinoox\Portal\Wizard\AppWizard;

class AppController extends ApiController
{
    const manualPath = 'downloads/packages/manual/';

    public function get($filter = null)
    {
        switch ($filter) {
            case 'installed':
            {
                $result = AppHelper::getAll(false);
                break;
            }
            case 'systems':
            {
                $result = AppHelper::getAll(true);
                break;
            }
            default:
            {
                $result = AppHelper::getAll(null, true);
            }
        }

        return $result;
    }

    public function getConfig($packageName)
    {
        return AppHelper::getOne($packageName);
    }

    public function setConfig(Request $request, $packageName, $key)
    {
        $config = $request->payload('config');

        if ($key == 'dock')
            $config = !$config;
        if ($key == 'router')
            $config = $config === 'multiple' ? 'single' : 'multiple';

        if (!is_null($config)) {
            AppEngine::config($packageName)
                ->set($key, $config)
                ->save();

            return $this->message('manager.config_saved_successfully');
        }

        return $this->deny('manager.invalid_request');
    }

    public function install(Request $request)
    {
        $this->validated($request, [
            'file' => [
                'file',
                function ($attribute, $value, $fail) {
                    if ($value->getClientOriginalExtension() !== 'pin') {
                        $fail('آپلود فایل با پسوند .pin �
جاز است!');
                    }
                }
            ],
        ]);

        $result = File::upload('file')
            ->to('uploads/apps')
            ->diskOnly()
            ->save();

        if (!$result->success || empty($result->path)) {
            return $this->error('manager.error_happened');
        }

        $pin = $result->path;

        try {
            $wizard = AppWizard::open($pin);
            $wizard->migration(true);
            if (!$wizard->isInstalled())
                $wizard->install();
            else
                return $this->error('manager.error_happened');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->message('manager.installed_successfully');

    }

    public function getAll(Request $request)
    {
        return AppHelper::getAll();
    }

    public function iconPack()
    {
        return [
            'provider' => AppIconPack::info(),
            'defaults' => AppIconPack::systemDefaults(),
            'usage' => AppIconPack::usage(),
        ];
    }

    public function installPackage($filename)
    {
        if (empty($filename))
            return $this->deny('manager.request_install_app_not_valid');

        $pinFile = path(self::manualPath . $filename);
        if (!is_file($pinFile))
            return $this->deny('manager.request_install_app_not_valid');

        if (Wizard::installApp($pinFile)) {
            return $this->message('manager.installed_successfully');
        }

        $message = Wizard::getMessage();

        if (empty($message))
            return $this->deny('manager.request_install_app_not_valid');

        return $this->deny($message);
    }

    public function files()
    {
        $path = path(self::manualPath);
        if (!is_dir($path))
            return [];

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

        return array_values(array_filter($files));
    }

    public function deleteFile(Request $request)
    {
        $filename = $request->payload('filename');

        if (empty($filename))
            return $this->deny('manager.error_happened');

        $pinFile = path(self::manualPath . $filename);
        if (!is_file($pinFile))
            return $this->deny('manager.error_happened');

        Wizard::deletePackageFile($pinFile);

        return $this->message('manager.delete_successfully');
    }

    public function filesUpload(Request $request)
    {
        if (!$request->files->has('files'))
            return $this->deny('manager.invalid_request');

        $path = path(self::manualPath);
        if (!is_dir($path))
            mkdir($path, 0755, true);

        $files = $request->files->all('files');
        if (!is_array($files))
            $files = [$files];

        $uploaded = 0;
        foreach ($files as $file) {
            if ($file->getClientOriginalExtension() !== 'pin')
                continue;
            $file->move($path, $file->getClientOriginalName());
            $uploaded++;
        }

        if ($uploaded === 0)
            return $this->deny('manager.error_happened');

        return $this->message(
            $uploaded === 1 ? 'manager.file_uploaded_correctly' : 'manager.files_uploaded_correctly'
        );
    }

    public function remove($packageName)
    {
        if (empty($packageName))
            return $this->deny('manager.request_not_valid');

        Wizard::deleteApp($packageName);

        return $this->message('manager.delete_successfully');
    }
}
