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
use PHPUnit\Exception;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Validation\ValidationException;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\FileUploader;
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

        $path = 'uploads/apps/';

        $up = FileUploader::store($path, 'file')
            ->upload();

        $pin = $up->getResult('file');

        try {
            $wizard = AppWizard::open($pin);
            $wizard->migration(true);
            if(!$wizard->isInstalled())
                $wizard->install();
            else
                return $this->error('manager.error_happened');


        }catch (Exception $e){
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
}