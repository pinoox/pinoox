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

use pinoox\app\com_pinoox_manager\component\Wizard;
use pinoox\component\Cache;
use pinoox\component\Config;
use pinoox\component\Download;
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
        return ['server' => $server_version, 'client' => $client_version, 'isNewVersion' => $isNewVersion];
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

            Response::json($this->getVersions(), true);
        } else {
            Response::json(null, false);
        }
    }
}