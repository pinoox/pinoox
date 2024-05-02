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
use Pinoox\Component\app\AppProvider;
use Pinoox\Component\Dir;
use Pinoox\Component\File;
use Pinoox\Component\Lang;
use Pinoox\Component\Response;
use Pinoox\Component\Url;

class TemplateController extends LoginConfiguration
{
    public function get($packageName)
    {
        $folders = File::get_dir_folders(path("~apps>$packageName>theme>"));
        $templates = [];
        foreach ($folders as $folder) {
            $metaJson = $folder . 'meta.json';
            if (!file_exists($metaJson)) continue;

            $meta = json_decode(file_get_contents($metaJson), true);
            $coverDefault = Url::file('resources/theme.jpg');
            $folderName = basename($folder);
            $meta['cover'] = (!empty($meta['cover'])) ? Url::check(Url::file('~apps/' . $packageName . '/theme/' . $folderName. '/' . $meta['cover']), $coverDefault) : $meta['cover'];

            if (empty($meta['title'][Lang::current()])) {
                $first = reset($meta['title']);
                $meta['template_name'] = $first;
            } else {
                $meta['template_name'] = $meta['title'][Lang::current()];
            }
            $meta['folder'] = File::name($folder);

            AppProvider::app($packageName);
            $current = AppProvider::get('theme');

            $meta['activate'] = $current === $meta['folder'];
            $templates[] = $meta;
        }

        Response::json($templates, !empty($templates));
    }

    public function install($uid, $packageName)
    {
        if (empty($packageName))
            Response::json(t('manager.request_install_template_not_valid'), false);

        if (!Wizard::isInstalled($packageName))
            Response::json(t('manager.there_is_no_app'), false);

        $file = Wizard::getDownloadedTemplate($uid);
        $meta = Wizard::pullTemplateMeta($file);

        Wizard::installTemplate($file, $packageName, $meta);
        Response::json(t('manager.done_successfully'), true);
    }

    public function installPackage($filename)
    {
        if (empty($filename))
            Response::json(t('manager.request_install_template_not_valid'), false);

        $pinFile = Dir::path(self::manualPath . $filename);
        if (!is_file($pinFile))
            Response::json(t('manager.request_install_template_not_valid'), false);

        $meta = Wizard::pullTemplateMeta($pinFile);

        if (!Wizard::isInstalled($meta['app']))
            Response::json(t('manager.there_is_no_app'), false);

        if (Wizard::installTemplate($pinFile, $meta['app'], $meta)) {
            Response::json(t('manager.done_successfully'), true);
        } else {
            $message = Wizard::getMessage();
            if (empty($message))
                Response::json(t('manager.request_install_template_not_valid'), false);
            else
                Response::json($message, false);
        }
    }

    public function set($packageName, $folderName)
    {
        AppProvider::app($packageName);
        AppProvider::set('theme', $folderName);
        AppProvider::save();
    }

    public function remove($packageName, $folderName)
    {
        Wizard::deleteTemplate($packageName, $folderName);
        Response::json(t('manager.done_successfully'), true);
    }
}
