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

use App\com_pinoox_manager\Component\PackagePaths;
use Pinoox\Component\Cache\AppCacheManager;
use Pinoox\Component\Database\Patch\PatchToolkit;
use Pinoox\Component\Migration\Migrator;
use Pinoox\Component\Package\Pinx\PinxInstallResult;
use Pinoox\Component\Package\Pinx\PinxManifest;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Config;
use Pinoox\Portal\Date;
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
        return self::runInstall($pinxFile)->success;
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

    public static function updateApp(string $pinxFile, array $options = []): bool
    {
        $data = self::pullDataPackage($pinxFile);

        if (!self::isValidNamePackage($data['package_name'])) {
            self::deletePackageFile($pinxFile);
            return false;
        }

        if (!AppEngine::exists($data['package_name'])) {
            $result = self::runInstall($pinxFile, $options);

            return $result->success;
        }

        if (!self::checkVersion($data)) {
            return false;
        }

        $result = self::runInstall($pinxFile, $options);

        if (!$result->success) {
            self::$message = $result->message;

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
        AppRouteCleanup::deleteForPackage($packageName);

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

    public static function installTemplate(string $file, string $packageName, $meta = null, array $options = []): bool
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

            $result = self::runInstall($file, $options);

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

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function installFromManual(string $pinxFile, array $options = []): array
    {
        try {
            $meta = self::pullPackageMeta($pinxFile);

            if ($meta['type'] === 'theme') {
                if (!self::isValidNamePackage($meta['app'])) {
                    self::$message = t('manager.request_install_app_not_valid');

                    return self::installResponse(false, [], $meta);
                }

                if (!self::installTemplate($pinxFile, $meta['app'], $meta, $options)) {
                    return self::installResponse(false, [], $meta);
                }

                self::deletePackageFile($pinxFile);

                return self::installResponse(true, [], $meta);
            }

            if (!self::isValidNamePackage($meta['package_name'])) {
                self::$message = t('manager.request_install_app_not_valid');

                return self::installResponse(false, [], $meta);
            }

            if (($meta['install_mode'] ?? 'install') === 'update') {
                if (!self::checkVersion($meta)) {
                    return self::installResponse(false, [], $meta);
                }

                $result = self::runInstall($pinxFile, $options);

                if ($result->success) {
                    self::deletePackageFile($pinxFile);
                } else {
                    self::$message = $result->message;
                }

                return self::installResponse($result->success, $result->steps, $meta, $result);
            }

            $result = self::runInstall($pinxFile, $options);

            if (!$result->success) {
                self::$message = $result->message;

                return self::installResponse(false, $result->steps, $meta);
            }

            self::deletePackageFile($pinxFile);

            return self::installResponse(true, $result->steps, $meta, $result);
        } catch (\Throwable $e) {
            self::$message = $e->getMessage();

            return self::installResponse(false, [], []);
        }
    }

    public static function pullPackageMeta(string $pinxFile): array
    {
        $manifest = Pinx::manifest($pinxFile);

        if ($manifest->isTheme()) {
            return self::buildThemeMeta($pinxFile, $manifest);
        }

        return self::buildAppMeta($pinxFile, $manifest);
    }

    private static function runInstall(string $pinxFile, array $options = []): PinxInstallResult
    {
        $sessionId = isset($options['session_id']) ? (string) $options['session_id'] : null;
        $databaseOptions = is_array($options['database'] ?? null) ? $options['database'] : null;

        $installer = Pinx::installer()->onStep(
            static function (string $step, string $status, string $message) use ($sessionId) {
                if ($sessionId !== null && $sessionId !== '') {
                    InstallSession::addStep($sessionId, $step, $status, $message);
                }
            },
        );

        try {
            $manifest = Pinx::manifest($pinxFile);
            $analysis = $manifest->isApp()
                ? PackageDatabase::analyzeFromPinx($pinxFile, $manifest->package())
                : [];
            $needsDatabasePass = $manifest->isApp()
                && ($databaseOptions !== null || !empty($analysis['needs_prefix_setup']));

            $installOptions = $options;
            unset($installOptions['session_id'], $installOptions['database']);

            if ($needsDatabasePass) {
                $installOptions['skip_migrate'] = true;
                $installOptions['skip_patch'] = true;
                $installOptions['skip_cache'] = true;
            }

            $result = $installer->install($pinxFile, $installOptions);

            if (!$result->success) {
                self::$message = $result->message;

                return $result;
            }

            if ($needsDatabasePass && $manifest->isApp()) {
                $prefix = PackageDatabase::applyForPackage($manifest->package(), $databaseOptions);
                AppEngine::__rebuild();

                if ($sessionId !== null && $sessionId !== '') {
                    InstallSession::addStep(
                        $sessionId,
                        'database',
                        'ok',
                        'پیشوند جداول تنظیم شد: ' . $prefix,
                    );
                }

                $result = self::runPostInstallTasks(
                    $installer,
                    $manifest->package(),
                    $result,
                    $sessionId,
                    !empty($analysis['has_migrations']),
                );
            }

            return $result;
        } catch (\Throwable $e) {
            self::$message = $e->getMessage();

            return new PinxInstallResult(false, 'failed', PinxManifest::fromArray([]), [], $e->getMessage());
        }
    }

    private static function runPostInstallTasks(
        $installer,
        string $package,
        PinxInstallResult $result,
        ?string $sessionId,
        bool $runMigrations = true,
    ): PinxInstallResult {
        $steps = $result->steps;

        try {
            if ($runMigrations) {
                (new Migrator('platform'))->run();
                self::appendStep($steps, 'migrate', 'ok', 'مایگریشن‌های پلتفرم اجرا شد.', $sessionId);
                (new Migrator($package))->run();
                self::appendStep($steps, 'migrate', 'ok', 'مایگریشن‌های اپلیکیشن اجرا شد.', $sessionId);

                $toolkit = new PatchToolkit();
                $toolkit->package($package)->load();

                if ($toolkit->isSuccess() && $toolkit->getPatches() !== []) {
                    foreach ($toolkit->getPatches() as $patch) {
                        if ($patch['ran'] || !$patch['should_run']) {
                            continue;
                        }

                        $patch['instance']->run();
                        $toolkit->recordSuccess($patch['name'], $patch['checksum'], 0);
                    }

                    self::appendStep($steps, 'patch', 'ok', 'پچ‌های دیتابیس اعمال شد.', $sessionId);
                } else {
                    self::appendStep($steps, 'patch', 'skipped', 'پچی برای اجرا نبود.', $sessionId);
                }
            } else {
                self::appendStep($steps, 'migrate', 'skipped', 'مایگریشنی در بسته تعریف نشده است.', $sessionId);
                self::appendStep($steps, 'patch', 'skipped', 'پچی برای اجرا نبود.', $sessionId);
            }

            AppCacheManager::build($package, null, true);
            self::appendStep($steps, 'cache', 'ok', 'کش اپلیکیشن بازسازی شد.', $sessionId);

            return new PinxInstallResult(true, $result->mode, $result->manifest, $steps, $result->message);
        } catch (\Throwable $e) {
            self::appendStep($steps, 'failed', 'error', $e->getMessage(), $sessionId);
            self::$message = $e->getMessage();

            return new PinxInstallResult(false, 'failed', $result->manifest, $steps, $e->getMessage());
        }
    }

    /**
     * @param list<array{step: string, status: string, message: string}> $steps
     */
    private static function appendStep(array &$steps, string $step, string $status, string $message, ?string $sessionId): void
    {
        $steps[] = [
            'step' => $step,
            'status' => $status,
            'message' => $message,
        ];

        if ($sessionId !== null && $sessionId !== '') {
            InstallSession::addStep($sessionId, $step, $status, $message);
        }
    }

    /**
     * @param list<array{step: string, status: string, message: string}> $steps
     * @return array<string, mixed>
     */
    private static function installResponse(
        bool $success,
        array $steps,
        array $meta,
        ?PinxInstallResult $result = null,
    ): array {
        $package = $meta['package_name'] ?? $meta['package'] ?? null;
        $routerConfig = null;
        $isRoutable = false;

        if ($success && is_string($package) && AppEngine::exists($package)) {
            $routerConfig = AppEngine::config($package)->get('router');
            $isRoutable = AppRoutePolicy::isRoutable($routerConfig);
        }

        return [
            'success' => $success,
            'message' => $success
                ? ($result?->message ?? t('manager.installed_successfully'))
                : (self::$message ?? t('manager.error_happened')),
            'steps' => $steps,
            'meta' => $meta,
            'package_name' => $package,
            'is_routable' => $isRoutable,
            'router_mode' => AppRoutePolicy::resolveMode($routerConfig),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildAppMeta(string $pinxFile, PinxManifest $manifest): array
    {
        $installMode = AppEngine::exists($manifest->package()) ? 'update' : 'install';
        $iconDataUri = self::resolvePinxIcon($pinxFile, $manifest);
        $packagedApp = self::readPackagedAppConfig($pinxFile);
        $routerConfig = $packagedApp['router'] ?? null;

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
            'has_icon' => $iconDataUri !== null,
            'icon' => $iconDataUri ?: Url::asset('resources/default.png'),
            'size' => File::print_size(File::size($pinxFile), 1),
            ...self::fileUploadMeta($pinxFile),
            'compatibility' => PackageCompatibility::analyze($manifest),
            'database' => PackageDatabase::analyzeFromPinx($pinxFile, $manifest->package()),
            'is_routable' => AppRoutePolicy::isRoutable($routerConfig),
            'router_mode' => AppRoutePolicy::resolveMode($routerConfig),
        ];
    }

    /**
     * @return array{uploaded_at: int, uploaded_at_label: string}
     */
    private static function fileUploadMeta(string $pinxFile): array
    {
        $timestamp = @filemtime($pinxFile) ?: time();

        return [
            'uploaded_at' => $timestamp,
            'uploaded_at_label' => Date::usingCalendar('jalali')->smart(
                date('Y-m-d H:i:s', $timestamp),
                'd F Y - H:i',
            ),
        ];
    }

    private static function resolvePinxIcon(string $pinxFile, PinxManifest $manifest): ?string
    {
        if (!$manifest->hasIcon()) {
            return null;
        }

        return Pinx::withReader($pinxFile, static fn ($reader) => $reader->iconDataUri());
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildThemeMeta(string $pinxFile, PinxManifest $manifest): array
    {
        $installMode = self::isInstalledTemplate($manifest->targetApp(), $manifest->themeName()) ? 'update' : 'install';
        $iconDataUri = self::resolvePinxIcon($pinxFile, $manifest);

        return [
            'type' => 'theme',
            'install_mode' => $installMode,
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
            'has_icon' => $iconDataUri !== null,
            'icon' => $iconDataUri,
            'cover' => $iconDataUri ?: Url::asset('resources/theme.jpg'),
            'size' => File::print_size(File::size($pinxFile), 1),
            ...self::fileUploadMeta($pinxFile),
            'compatibility' => PackageCompatibility::analyze($manifest),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function readPackagedAppConfig(string $pinxFile): array
    {
        try {
            return Pinx::withReader($pinxFile, static function ($reader) {
                $zip = $reader->zip();
                $entry = PinxManifest::PAYLOAD_PREFIX . 'app.php';

                if (!$zip->hasEntry($entry)) {
                    return [];
                }

                $file = tempnam(sys_get_temp_dir(), 'pinx_cfg_');

                if ($file === false) {
                    return [];
                }

                try {
                    file_put_contents($file, $zip->getEntryContents($entry));
                    $loaded = include $file;

                    return is_array($loaded) ? $loaded : [];
                } finally {
                    @unlink($file);
                }
            });
        } catch (\Throwable) {
            return [];
        }
    }

    private static function appDownloadPath(string $package_name): string
    {
        return PackagePaths::appsFile($package_name);
    }

    private static function templateDownloadPath(string $uid): string
    {
        return PackagePaths::templatesFile($uid);
    }
}
