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

use App\com_pinoox_manager\Component\Wizard;
use Pinoox\Component\File;
use Pinoox\Component\Template\Theme\ThemeManifest;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Lang;
use Pinoox\Portal\Url;

class TemplateController extends Api
{
    const manualPath = 'downloads/packages/manual/';

    public function get($packageName)
    {
        $themePath = path('~apps/' . $packageName . '/theme/');
        if (!is_dir($themePath))
            return $this->message(null, false);

        $folders = File::get_dir_folders($themePath);
        $templates = [];
        $current = AppEngine::config($packageName)->get('theme');

        foreach (ThemeManifest::discover($packageName) as $folderName => $manifest) {
            if (!ThemeManifest::hasManifest($manifest->path())) {
                continue;
            }
            $meta = $manifest->toArray();
            $coverDefault = Url::asset('resources/theme.jpg');

            if ($manifest->cover() !== '') {
                $meta['cover'] = Url::check(
                    Url::reference('~apps/' . $packageName . '/theme/' . $folderName . '/' . $manifest->cover()),
                    $coverDefault,
                );
            } else {
                $meta['cover'] = $coverDefault;
            }

            $meta['template_name'] = $manifest->title(Lang::locale());
            $meta['folder'] = $folderName;
            $meta['activate'] = $current === $folderName;
            $meta['extends'] = $manifest->extends();
            $templates[] = $meta;
        }

        return $templates;
    }

    public function install($uid, $packageName)
    {
        if (empty($packageName))
            return $this->message(t('manager.request_install_template_not_valid'), false);

        if (!Wizard::isInstalled($packageName))
            return $this->message(t('manager.there_is_no_app'), false);

        $file = Wizard::getDownloadedTemplate($uid);
        $meta = Wizard::pullTemplateMeta($file);

        if (Wizard::installTemplate($file, $packageName, $meta))
            return $this->message(t('manager.done_successfully'));

        return $this->message(Wizard::getMessage() ?: t('manager.request_install_template_not_valid'), false);
    }

    public function installPackage($filename)
    {
        if (empty($filename))
            return $this->message(t('manager.request_install_template_not_valid'), false);

        $pinFile = path(self::manualPath . $filename);
        if (!is_file($pinFile))
            return $this->message(t('manager.request_install_template_not_valid'), false);

        $meta = Wizard::pullTemplateMeta($pinFile);

        if (!Wizard::isInstalled($meta['app']))
            return $this->message(t('manager.there_is_no_app'), false);

        if (Wizard::installTemplate($pinFile, $meta['app'], $meta))
            return $this->message(t('manager.done_successfully'));

        return $this->message(Wizard::getMessage() ?: t('manager.request_install_template_not_valid'), false);
    }

    public function set($packageName, $folderName)
    {
        AppEngine::config($packageName)
            ->set('theme', $folderName)
            ->save();

        return $this->message($folderName);
    }

    public function remove($packageName, $folderName)
    {
        Wizard::deleteTemplate($packageName, $folderName);
        return $this->message(t('manager.done_successfully'));
    }
}

