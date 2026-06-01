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

        foreach ($folders as $folder) {
            $metaJson = $folder . 'meta.json';
            if (!file_exists($metaJson))
                continue;

            $meta = json_decode(file_get_contents($metaJson), true);
            $coverDefault = Url::path('resources/theme.jpg');
            $folderName = basename($folder);

            if (!empty($meta['cover']))
                $meta['cover'] = Url::check(Url::file('~apps/' . $packageName . '/theme/' . $folderName . '/' . $meta['cover']), $coverDefault);

            if (empty($meta['title'][Lang::locale()]))
                $meta['template_name'] = reset($meta['title']) ?: $folderName;
            else
                $meta['template_name'] = $meta['title'][Lang::locale()];

            $meta['folder'] = File::name($folder);
            $meta['activate'] = $current === $meta['folder'];
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
