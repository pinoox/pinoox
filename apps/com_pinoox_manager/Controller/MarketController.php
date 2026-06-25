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
use Pinoox\Component\Http\Http;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Config;
use Pinoox\Portal\Url;

class MarketController extends ApiController
{
    public function getDownloads()
    {
        $market = Config::name('market')->get() ?? [];
        $result = [];

        foreach ($market as $package_name => $data) {
            if (Wizard::isDownloaded($package_name)) {
                $data['package_name'] = $package_name;
                $data['state'] = Wizard::app_state($package_name);
                $result[] = $data;
            }
        }

        return $result;
    }

    public function deleteDownload(Request $request)
    {
        $package_name = $request->payload('package_name');

        if (empty($package_name))
            return $this->deny('manager.error_happened');

        $pinFile = Wizard::getDownloaded($package_name);
        if (!is_file($pinFile))
            return $this->deny('manager.error_happened');

        Wizard::deletePackageFile($pinFile);
        Config::name('market')->remove($package_name)->save();

        return $this->message('manager.delete_successfully');
    }

    private function getAuthParams(array $auth): array
    {
        $pinVer = config('~pinoox');
        return [
            'token' => $auth['token'] ?? null,
            'remote_url' => Url::origin(),
            'user_agent' => ($_SERVER['HTTP_USER_AGENT'] ?? 'Pinoox') . ';Pinoox/' . ($pinVer['version_name'] ?? '') . ' Manager',
        ];
    }

    public function getApps($keyword = '')
    {
        $response = Http::get('https://www.pinoox.com/api/manager/v1/market/get/' . $keyword);
        if (!$response)
            return [];

        return response($response->getContent(), $response->getStatusCode(), ['Content-Type' => 'application/json']);
    }

    public function getOneApp($package_name)
    {
        $response = Http::get("https://www.pinoox.com/api/manager/v1/market/getApp/" . $package_name);
        if (!$response)
            return $this->deny('manager.error_happened');

        $arr = json_decode($response->getContent(), true) ?? [];
        $arr['state'] = Wizard::app_state($package_name);

        return $arr;
    }

    public function downloadRequest(Request $request, $package_name)
    {
        if (AppEngine::exists($package_name))
            return $this->deny('manager.currently_installed');

        $auth = $request->payload('auth', []);
        $params = $this->getAuthParams($auth);

        $response = Http::post('https://www.pinoox.com/api/manager/v1/market/downloadRequest/' . $package_name, [
            'json' => $params,
        ]);

        if (!$response)
            return $this->deny('manager.error_happened');

        $data = json_decode($response->getContent(), true);
        if (empty($data['status']))
            return response($response->getContent(), $response->getStatusCode(), ['Content-Type' => 'application/json']);

        $path = path(PackagePaths::APPS . $package_name . '.pinx');
        if ($this->downloadToFile('https://www.pinoox.com/api/manager/v1/market/download/' . $data['result']['hash'], $path)) {
            Config::name('market')->set($package_name, $data['result'])->save();

            return $this->message('manager.download_completed');
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

    public function getTemplates($package_name)
    {
        $response = Http::get('https://www.pinoox.com/api/manager/v1/market/getAppTemplates/' . $package_name);
        if (!$response)
            return [];

        $result = json_decode($response->getContent(), true) ?? [];
        $templates = [];

        foreach ($result as $t) {
            $t['state'] = Wizard::templateState($package_name, $t['uid']);
            $t['type'] = 'theme';
            $templates[] = $t;
        }

        return $templates;
    }

    public function downloadRequestTemplate(Request $request, $uid)
    {
        $data = $request->payloadMany('*');
        $params = $this->getAuthParams($data['auth'] ?? []);

        if (empty($data['package_name']) || !Wizard::isInstalled($data['package_name']))
            return $this->deny('manager.there_is_no_app');

        $response = Http::post('https://www.pinoox.com/api/manager/v1/market/downloadRequestTemplate/' . $uid, [
            'json' => $params,
        ]);

        if (!$response)
            return $this->deny('manager.error_happened');

        $result = json_decode($response->getContent(), true);
        if (empty($result['status']))
            return response($response->getContent(), $response->getStatusCode(), ['Content-Type' => 'application/json']);

        $path = path(PackagePaths::TEMPLATES . $uid . '.pinx');
        if ($this->downloadToFile('https://www.pinoox.com/api/manager/v1/market/downloadTemplate/' . $result['result']['hash'], $path)) {
            return $this->message('manager.download_completed');
        }

        return $this->deny('manager.error_happened');
    }
}
