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

namespace pinoox\app\com_pinoox_manager\controller\api\v1;

use pinoox\app\com_pinoox_manager\component\Notification;
use pinoox\app\com_pinoox_manager\component\Wizard;
use pinoox\component\Cache;
use pinoox\component\Config;
use pinoox\component\Download;
use pinoox\component\Lang;
use pinoox\component\Response;

class UpdateController extends LoginConfiguration
{
    public function checkVersion($type = 'none')
    {
        if ($type === 'force')
            Cache::clean('version');
        Response::json($this->getVersions());
    }

    private function getVersions()
    {
        $server_version = Cache::get('version');
        $client_version = Config::get('~pinoox');
        $client_version = [
            'version_code' => $client_version['version_code'],
            'version_name' => $client_version['version_name'],
        ];
        $server_version_code = (isset($server_version['version_code'])) ? $server_version['version_code'] : 0;
        $isNewVersion = ($server_version_code > $client_version['version_code']);

        if ($isNewVersion)
            $this->notificationCheckVersion($server_version);

        return ['server' => $server_version, 'client' => $client_version, 'isNewVersion' => $isNewVersion];
    }

    private function notificationCheckVersion($version)
    {
        $title = Lang::get('notification.release_new_version.title');
        $message = Lang::replace('notification.release_new_version.message', ['version' => $version['version_name']]);

        Notification::action('release_new_version_' . $version['version_code'], $version);
        Notification::push($title, $message, 0, true);
    }

    public function install()
    {
        Cache::clean('version');
        $server_version = Cache::get('version');
        $clint_version = Config::get('~pinoox');
        $server_version_code = (isset($server_version['version_code'])) ? $server_version['version_code'] : 0;
        $isNewVersion = ($server_version_code > $clint_version['version_code']);
        if ($isNewVersion) {
            $file = path('temp/pincore.pin');
            Download::fetch('https://www.pinoox.com/api/v1/update/get', $file)->process();
            Wizard::updateCore($file);

            $this->notificationInstall($server_version);
            Response::json($this->getVersions(), true);
        } else {
            Response::json(null, false);
        }
    }

    private function notificationInstall($version)
    {
        $title = Lang::get('notification.update_new_version.title');
        $message = Lang::replace('notification.update_new_version.message', ['version' => $version['version_name']]);

        Notification::action('update_new_version_' . $version['version_code'], $version);
        Notification::push($title, $message, 0, true);
    }
}