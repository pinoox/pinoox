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
use App\com_pinoox_manager\Component\PackagePaths;
use App\com_pinoox_manager\Component\Wizard;
use Pinoox\Component\File as FileHelper;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Portal\App\AppEngine;

class AppController extends ApiController
{
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
        if ($key == 'router') {
            $routerConfig = AppEngine::config($packageName)->get('router');

            if (!is_array($routerConfig)) {
                $routerConfig = ['routes' => []];
            }

            $routerConfig['type'] = $config === 'multiple' ? 'single' : 'multiple';
            $config = $routerConfig;
        }

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
                    if (strtolower($value->getClientOriginalExtension()) !== 'pinx') {
                        $fail('آپلود فایل با پسوند .pinx مجاز است!');
                    }
                }
            ],
        ]);

        PackagePaths::ensureManualDir();

        $upload = $request->files->get('file');
        $filename = $upload->getClientOriginalName();
        $upload->move(PackagePaths::manualDir(), $filename);

        $pinxFile = PackagePaths::manualFile($filename);

        if (Wizard::installFromManual($pinxFile)) {
            return $this->message('manager.installed_successfully');
        }

        $message = Wizard::getMessage();

        if (empty($message)) {
            return $this->error('manager.error_happened');
        }

        return $this->deny($message);
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

        $filename = basename($filename);
        $pinxFile = PackagePaths::manualFile($filename);
        if (!is_file($pinxFile))
            return $this->deny('manager.request_install_app_not_valid');

        if (Wizard::installFromManual($pinxFile)) {
            return $this->message('manager.installed_successfully');
        }

        $message = Wizard::getMessage();

        if (empty($message))
            return $this->deny('manager.request_install_app_not_valid');

        return $this->deny($message);
    }

    public function packageMeta($filename)
    {
        if (empty($filename))
            return $this->deny('manager.error_happened');

        $filename = basename($filename);
        $pinxFile = PackagePaths::manualFile($filename);

        if (!is_file($pinxFile))
            return $this->deny('manager.error_happened');

        try {
            return Wizard::pullPackageMeta($pinxFile);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function files()
    {
        $path = PackagePaths::manualDir();
        if (!is_dir($path))
            return [];

        $files = FileHelper::get_files_by_pattern($path, '*.pinx');
        $files = array_map(function ($file) {
            try {
                $data = Wizard::pullPackageMeta($file);
            } catch (\Throwable) {
                Wizard::deletePackageFile($file);
                return false;
            }

            if ($data['type'] === 'app' && !Wizard::isValidNamePackage($data['package_name'])) {
                Wizard::deletePackageFile($file);
                return false;
            }

            if ($data['type'] === 'theme' && !Wizard::isValidNamePackage($data['app'])) {
                Wizard::deletePackageFile($file);
                return false;
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

        $pinxFile = PackagePaths::manualFile($filename);
        if (!is_file($pinxFile))
            return $this->deny('manager.error_happened');

        Wizard::deletePackageFile($pinxFile);

        return $this->message('manager.delete_successfully');
    }

    public function filesUpload(Request $request)
    {
        if (!$request->files->has('files'))
            return $this->deny('manager.invalid_request');

        $path = PackagePaths::ensureManualDir();

        $files = $request->files->all('files');
        if (!is_array($files))
            $files = [$files];

        $uploaded = 0;
        foreach ($files as $file) {
            if (strtolower($file->getClientOriginalExtension()) !== 'pinx')
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
