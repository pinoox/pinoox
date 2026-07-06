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

use App\com_pinoox_manager\Component\NotificationHelper;
use App\com_pinoox_manager\Component\Wizard;
use Pinoox\Component\Http\Http;
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Portal\Cache;
use Pinoox\Portal\Config;

class UpdateController extends ApiController
{
    public function checkVersion($type = 'none')
    {
        if ($type === 'force')
            Cache::delete('version');

        return $this->getVersions();
    }

    private function getVersions(): array
    {
        $server_version = Cache::get('version') ?? [];
        $client = [
            'version_code' => (int) config('~pinoox.version_code', 0),
            'version_name' => (string) config('~pinoox.version_name', ''),
        ];
        $server_version_code = $server_version['version_code'] ?? 0;
        $isNewVersion = ($server_version_code > $client['version_code']);

        if ($isNewVersion)
            $this->notificationCheckVersion($server_version);

        return [
            'server' => $server_version,
            'client' => $client,
            'isNewVersion' => $isNewVersion,
        ];
    }

    private function notificationCheckVersion(array $version): void
    {
        $title = t('notification.release_new_version.title');
        $message = t('notification.release_new_version.message', ['version' => $version['version_name'] ?? '']);

        NotificationHelper::push($title, $message, 0, true, 'release_new_version_' . ($version['version_code'] ?? 0), $version);
    }

    public function install()
    {
        Cache::delete('version');
        $server_version = Cache::get('version') ?? [];
        $client_version_code = (int) config('~pinoox.version_code', 0);
        $server_version_code = $server_version['version_code'] ?? 0;
        $isNewVersion = ($server_version_code > $client_version_code);

        if (!$isNewVersion)
            return $this->deny('manager.update_not_available');

        $file = path('temp/pincore.pin');
        if ($this->downloadToFile('https://www.pinoox.com/api/v1/update/get', $file)) {
            Wizard::updateCore($file);
            $this->notificationInstall($server_version);

            return $this->message('manager.update_successfully', $this->getVersions());
        }

        return $this->deny('manager.error_happened');
    }

    private function downloadToFile(string $url, string $targetPath): bool
    {
        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $response = Http::get($url, ['sink' => $targetPath]);

        return $response !== null && is_file($targetPath) && filesize($targetPath) > 0;
    }

    private function notificationInstall(array $version): void
    {
        $title = t('notification.update_new_version.title');
        $message = t('notification.update_new_version.message', ['version' => $version['version_name'] ?? '']);

        NotificationHelper::push($title, $message, 0, true, 'update_new_version_' . ($version['version_code'] ?? 0), $version);
    }
}

