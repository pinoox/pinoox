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

use Pinoox\Component\Package\Pinx\PinxManifest;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Config;
use Pinoox\Component\File;
use Pinoox\Portal\FileSystem;
use Pinoox\Portal\Lang;
use Pinoox\Portal\Pinx;
use Pinoox\Portal\Url;
use Pinoox\Portal\Zip;

class Wizard
{
    private const PACKAGE_EXT = '.pinx';

    private static ?string $message = null;

    public static function installApp(string $pinxFile): bool
    {
        return self::runInstall($pinxFile);
    }

    public static function pullDataPackage(string $pinxFile): array
    {
        $meta = self::pullPackageMeta($pinxFile);

        if ($meta['type'] !== 'app') {
            throw new \RuntimeException('Package is not an app.');
        }

        return $meta;
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

    public static function deletePackageFile(string $pinxFile): void
    {
        FileSystem::remove($pinxFile);
    }

    public static function updateApp(string $pinxFile): bool
    {
        $data = self::pullDataPackage($pinxFile);

        if (!self::isValidNamePackage($data['package_name'])) {
            self::deletePackageFile($pinxFile);
            return false;
        }

        if (!AppEngine::exists($data['package_name'])) {
            return self::installApp($pinxFile);
        }

        if (!self::checkVersion($data)) {
            return false;
        }

        if (!self::runInstall($pinxFile)) {
            return false;
        }

        self::deletePackageFile($pinxFile);

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
        $result = Pinx::uninstallApp($packageName);

        if (!$result->success) {
            self::$message = $result->message;

            return false;
        }

        return true;
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
        $file = self::appDownloadPath($package_name);
        return is_file($file);
    }

    public static function getDownloaded($package_name)
    {
        return self::appDownloadPath($package_name);
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
        $file = self::templateDownloadPath($uid);
        return is_file($file);
    }

    public static function getDownloadedTemplate($uid)
    {
        return self::templateDownloadPath($uid);
    }

    public static function installTemplate(string $file, string $packageName, $meta = null): bool
    {
        try {
            $manifest = Pinx::manifest($file);

            if (!$manifest->isTheme()) {
                self::$message = 'Package is not a theme.';
                return false;
            }

            if ($manifest->targetApp() !== $packageName) {
                self::$message = 'Theme target app mismatch.';
                return false;
            }

            return self::runInstall($file);
        } catch (\Throwable $e) {
            self::$message = $e->getMessage();
            return false;
        }
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

    public static function pullTemplateMeta(string $pinxFile): array
    {
        $meta = self::pullPackageMeta($pinxFile);

        if ($meta['type'] !== 'theme') {
            throw new \RuntimeException('Package is not a theme.');
        }

        return $meta;
    }

    public static function pullPackageMeta(string $pinxFile): array
    {
        $manifest = Pinx::manifest($pinxFile);

        if ($manifest->isTheme()) {
            return self::buildThemeMeta($pinxFile, $manifest);
        }

        return self::buildAppMeta($pinxFile, $manifest);
    }

    private static function runInstall(string $pinxFile, array $options = []): bool
    {
        try {
            $result = Pinx::install($pinxFile, $options);

            if (!$result->success) {
                self::$message = $result->message;
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            self::$message = $e->getMessage();
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildAppMeta(string $pinxFile, PinxManifest $manifest): array
    {
        $locale = Lang::locale();
        $installMode = AppEngine::exists($manifest->package()) ? 'update' : 'install';

        return [
            'type' => 'app',
            'install_mode' => $installMode,
            'filename' => File::fullname($pinxFile),
            'package_name' => $manifest->package(),
            'package' => $manifest->package(),
            'app' => $manifest->package(),
            'name' => $manifest->name(),
            'description' => $manifest->description(),
            'version' => $manifest->versionName(),
            'version-code' => $manifest->versionCode(),
            'version_code' => $manifest->versionCode(),
            'developer' => $manifest->developer(),
            'path_icon' => $manifest->icon() ?: 'icon.png',
            'icon_entry' => $manifest->iconEntry(),
            'has_icon' => $manifest->hasIcon(),
            'icon' => Url::asset('resources/default.png'),
            'size' => File::print_size(File::size($pinxFile), 1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildThemeMeta(string $pinxFile, PinxManifest $manifest): array
    {
        return [
            'type' => 'theme',
            'filename' => File::fullname($pinxFile),
            'template_name' => $manifest->name(),
            'app' => $manifest->targetApp(),
            'name' => $manifest->themeName(),
            'title' => $manifest->name(),
            'description' => $manifest->description(),
            'version' => $manifest->versionName(),
            'version-code' => $manifest->versionCode(),
            'developer' => $manifest->developer(),
            'path_cover' => '',
            'cover' => Url::asset('resources/theme.jpg'),
            'size' => File::print_size(File::size($pinxFile), 1),
        ];
    }

    private static function appDownloadPath(string $package_name): string
    {
        return path('downloads/apps/' . $package_name . self::PACKAGE_EXT);
    }

    private static function templateDownloadPath(string $uid): string
    {
        return path('downloads/templates/' . $uid . self::PACKAGE_EXT);
    }
}
