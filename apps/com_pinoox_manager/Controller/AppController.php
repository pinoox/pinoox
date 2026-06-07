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
use App\com_pinoox_manager\Component\Wizard;
use Pinoox\Component\File;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Validation\ValidationException;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppRouter;
use Pinoox\Portal\File;
use Pinoox\Portal\Wizard\AppWizard;

class AppController extends Api
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
        $config = $request->getPayload()->get('config');

        if ($key == 'dock')
            $config = !$config;
        if ($key == 'router')
            $config = $config === 'multiple' ? 'single' : 'multiple';

        if (!is_null($config)) {
            AppEngine::config($packageName)
                ->set($key, $config)
                ->save();
            return $this->message($config);
        } else {
            return $this->message(null, false);
        }
    }

    public function install(Request $request)
    {
        try {
            $request->validate([
                'file' => [
                    'file',
                    function ($attribute, $value, $fail) {
                        if ($value->getClientOriginalExtension() !== 'pin') {
                            $fail('آپلود فایل با پسوند .pin مجاز است!');
                        }
                    }
                ],
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->first());
        }

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
            if(!$wizard->isInstalled())
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

    public function installPackage($filename)
    {
        if (empty($filename))
            return $this->message(t('manager.request_install_app_not_valid'), false);

        $pinFile = path(self::manualPath . $filename);
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
        $filename = $request->json->get('filename');

        if (empty($filename))
            return $this->message(t('manager.error_happened'), false);

        $pinFile = path(self::manualPath . $filename);
        if (!is_file($pinFile))
            return $this->message(t('manager.error_happened'), false);

        Wizard::deletePackageFile($pinFile);
        return $this->message(t('manager.delete_successfully'));
    }

    public function filesUpload(Request $request)
    {
        if (!$request->files->has('files'))
            return $this->message(t('manager.invalid_request'), false);

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
            return $this->message(t('manager.error_happened'), false);

        return $this->message($uploaded === 1 ? t('manager.file_uploaded_correctly') : t('manager.files_uploaded_correctly'));
    }

    public function remove($packageName)
    {
        if (empty($packageName))
            return $this->message(t('manager.request_not_valid'), false);

        Wizard::deleteApp($packageName);
        return $this->message(t('manager.done_successfully'));
    }
}