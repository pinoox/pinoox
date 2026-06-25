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

use App\com_pinoox_manager\Component\PackagePaths;
use App\com_pinoox_manager\Component\Wizard;
use Pinoox\Component\File;
use Pinoox\Component\Template\Theme\ThemeManifest;
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Lang;
use Pinoox\Portal\Url;

class TemplateController extends ApiController
{
    public function get($packageName)
    {
        $themePath = path('~apps/' . $packageName . '/theme/');
        if (!is_dir($themePath))
            return $this->deny('manager.there_is_no_app');

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
            return $this->deny('manager.request_install_template_not_valid');

        if (!Wizard::isInstalled($packageName))
            return $this->deny('manager.there_is_no_app');

        $file = Wizard::getDownloadedTemplate($uid);
        $meta = Wizard::pullTemplateMeta($file);

        if (Wizard::installTemplate($file, $packageName, $meta))
            return $this->message('manager.installed_successfully');

        return $this->deny(Wizard::getMessage() ?: 'manager.request_install_template_not_valid');
    }

    public function installPackage($filename)
    {
        if (empty($filename))
            return $this->deny('manager.request_install_template_not_valid');

        $pinFile = PackagePaths::manualFile($filename);
        if (!is_file($pinFile))
            return $this->deny('manager.request_install_template_not_valid');

        $meta = Wizard::pullTemplateMeta($pinFile);

        if (!Wizard::isInstalled($meta['app']))
            return $this->deny('manager.there_is_no_app');

        if (Wizard::installTemplate($pinFile, $meta['app'], $meta))
            return $this->message('manager.installed_successfully');

        return $this->deny(Wizard::getMessage() ?: 'manager.request_install_template_not_valid');
    }

    public function set($packageName, $folderName)
    {
        AppEngine::config($packageName)
            ->set('theme', $folderName)
            ->save();

        return $this->message('manager.template_activated_successfully');
    }

    public function remove($packageName, $folderName)
    {
        Wizard::deleteTemplate($packageName, $folderName);

        return $this->message('manager.delete_successfully');
    }
}
