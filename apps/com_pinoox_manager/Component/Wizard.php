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


namespace App\com_pinoox_manager\Component;


use Pinoox\Component\Migration\MigrationToolkit;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppRouter;
use Pinoox\Portal\Config;
use Pinoox\Component\File;
use Pinoox\Portal\FileSystem;
use Pinoox\Portal\Lang;
use Pinoox\Portal\Url;
use Pinoox\Portal\Wizard\AppWizard;
use Pinoox\Portal\Wizard\TemplateWizard;
use Pinoox\Portal\Zip;

class Wizard
{
    private static $isApp = false;
    private static $message = null;

    public static function installApp($pinFile)
    {
        $appWizard = AppWizard::open($pinFile)
            ->migration();

        if (!$appWizard->isInstalled())
            $appWizard->install();

        return true;
    }

    public static function pullDataPackage($pinFile)
    {
        $filename = File::fullname($pinFile);
        $size = File::size($pinFile);

        $appWizard = AppWizard::open($pinFile);
        $info = $appWizard->getInfo();
        $info = !empty($info) ? $info : [];
        $defaultData = AppEngine::getDefaultData();
        $info = array_merge($defaultData, $info);
        $icon = Url::path('resources/default.png');
        if (!empty($info['icon'])) {
            $iconFile = $info['icon_path'];
            if (is_file($iconFile))
                $icon = Url::path($info['icon_path']);
        }

        return [
            'type' => 'app',
            'filename' => $filename,
            'package_name' => $info['package'],
            'package' => $info['package'],
            'app' => $info['package'],
            'name' => $info['name'],
            'description' => $info['description'],
            'version' => $info['version-name'],
            'version-code' => $info['version-code'],
            'version_code' => $info['version-code'],
            'developer' => $info['developer'],
            'path_icon' => $info['icon'],
            'icon' => $icon,
            'size' => File::print_size($size, 1),
        ];
    }

    public static function isValidNamePackage($packageName)
    {
        return AppEngine::checkName($packageName);
    }

    public static function checkVersion($data)
    {
        $packageName = $data['package_name'];
        $versionCode = @$data['version-code'];

        if (!AppEngine::exists($packageName))
            return true;

        $app = AppEngine::config($packageName);
        $versionCodeApp = $app->get('version-code');

        if ($versionCodeApp == $versionCode) {
            self::$message = t('manager.version_already_installed');
            return false;
        } else if ($versionCodeApp > $versionCode) {
            self::$message = t('manager.newer_version_installed');
            return false;
        }

        return true;
    }

    public static function changeLang($package_name)
    {
        $lang = Lang::locale();
        AppEngine::config($package_name)
            ->set('lang', $lang)
            ->save();
        return true;
    }

    public static function deletePackageFile($pinFile)
    {
        AppWizard::open($pinFile)->deleteTemp();
        FileSystem::remove($pinFile);
    }

    public static function updateApp($pinFile)
    {
        $data = self::pullDataPackage($pinFile);

        if (!self::isValidNamePackage($data['package_name'])) {
            self::deletePackageFile($pinFile);
            return false;
        }

        $appWizard = AppWizard::open($pinFile)->migration();

        if (!$appWizard->isUpdateAvailable())
            return false;

        if (!self::checkVersion($data))
            return false;

        $appWizard->force()->install();

        $app = AppEngine::config($data['package']);
        $app->set('version-code', $data['version-code']);
        $app->set('version-name', $data['version']);
        $app->set('name', $data['name']);
        $app->set('developer', $data['developer']);
        $app->set('description', $data['description']);
        $app->set('icon', $data['path_icon']);
        $app->save();

        self::deletePackageFile($pinFile);

        return true;

    }

    public static function getMessage()
    {
        $message = self::$message;
        self::$message = null;
        return $message;
    }

    public static function deleteApp($packageName)
    {
        $appPath = path('~apps/' . $packageName);
        File::remove($appPath);

        //remove route
        AppRouter::deletePackage($packageName);

        //remove database
        $mig = new MigrationToolkit();
        $mig->package($packageName)
            ->action('rollback')
            ->load();
    }

    public static function updateCore($file)
    {
        Zip::openFile($file)
            ->extractTo(path('~'));

        FileSystem::remove($file);
        Config::name('version')->restore();
    }

    public static function appState($package_name)
    {
        if (self::isInstalled($package_name))
            $state = 'installed';
        else if (self::isDownloaded($package_name))
            $state = 'install';
        else
            $state = 'download';

        return $state;
    }

    public static function isInstalled($package_name)
    {
        return AppEngine::exists($package_name);
    }

    public static function isDownloaded($package_name)
    {
        $file = path('downloads/apps/' . $package_name . '.pin');
        return (!empty($file) && file_exists($file));
    }

    public static function getDownloaded($package_name)
    {
        return path('downloads/apps/' . $package_name . '.pin');
    }

    public static function templateState($package_name, $uid)
    {
        if (self::isInstalledTemplate($package_name, $uid))
            $state = 'installed';
        else if (self::isDownloadedTemplate($uid))
            $state = 'install';
        else
            $state = 'download';

        return $state;
    }

    public static function isInstalledTemplate($package_name, $uid)
    {
        $file = path("~apps/$package_name/theme/$uid");
        return (!empty($file) && file_exists($file));
    }

    public static function isDownloadedTemplate($uid)
    {
        $file = path("downloads/templates/$uid.pin");
        return (!empty($file) && file_exists($file));
    }

    public static function getDownloadedTemplate($uid)
    {
        return path("downloads/templates/$uid.pin");
    }

    public static function installTemplate($file, $packageName, $meta)
    {
        //Todo install template
        return false;
    }

    public static function deleteTemplate($packageName, $folderName)
    {
        //Todo delete template
        $templatePath = path('~apps/' . $packageName . '/theme/' . $folderName);
        File::remove($templatePath);
    }

    public static function checkTemplateFolderName($packageName, $templateFolderName)
    {
        //todo check template folder
        $file = path("~apps/$packageName/theme/" . $templateFolderName);
        return file_exists($file);
    }

    public static function pullTemplateMeta($pinFile)
    {
        // todo template meta
        $filename = File::fullname($pinFile);
        $size = File::size($pinFile);
        $name = File::name($pinFile);


        $meta = TemplateWizard::open($pinFile)->getInfo();

        $cover = Url::path('resources/theme.jpg');

        if (empty($meta['title'])) {
            $title = null;
        } else if (empty($meta['title'][Lang::locale()])) {
            $title = array_values($meta['title'])[0];
        } else {
            $title = $meta['title'][Lang::locale()];
        }

        return [
            'type' => 'theme',
            'filename' => $filename,
            'template_name' => $title,
            'app' => @$meta['app'],
            'name' => @$meta['name'],
            'title' => @$meta['title'],
            'description' => @$meta['description'],
            'version' => @$meta['version'],
            'version-code' => @$meta['app_version'],
            'developer' => @$meta['developer'],
            'path_cover' => @$meta['cover'],
            'cover' => $cover,
            'size' => File::print_size($size, 1),
        ];
    }
}