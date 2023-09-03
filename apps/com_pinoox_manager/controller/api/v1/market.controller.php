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
use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\Config;
use pinoox\component\Dir;
use pinoox\component\Download;
use pinoox\component\HelperHeader;
use pinoox\component\Lang;
use pinoox\component\Request;
use pinoox\component\Response;
use pinoox\component\Url;

class MarketController extends LoginConfiguration
{
    public function getDownloads()
    {
        $result = AppModel::fetch_all_downloads();
        Response::json($result);
    }

    public function deleteDownload()
    {
        $package_name = Request::inputOne('package_name', null, '!empty');

        if (empty($package_name))
            Response::json(Lang::get('manager.error_happened'), false);

        $pinFile = Wizard::get_downloaded($package_name);
        if (!is_file($pinFile))
            Response::json(Lang::get('manager.error_happened'), false);

        Wizard::deletePackageFile($pinFile);
        Config::remove('market.' . $package_name);
        Config::save('market');
        Response::json(Lang::get('manager.delete_successfully'), true);
    }


    private function getAuthParams($auth)
    {
        $pinVer = Config::get('~pinoox');
        return [
            'token' => $auth['token'],
            'remote_url' => Url::site(),
            'user_agent' => HelperHeader::getUserAgent() . ';Pinoox/' . $pinVer['version_name'] . ' Manager',
        ];
    }

    public function getApps($keyword = '')
    {
        $data = Request::sendGet('https://www.pinoox.com/api/manager/v1/market/get/' . $keyword);
        HelperHeader::contentType('application/json', 'UTF-8');
        echo $data;
    }

    public function getOneApp($package_name)
    {
        $data = Request::sendGet("https://www.pinoox.com/api/manager/v1/market/getApp/" . $package_name);
        HelperHeader::contentType('application/json', 'UTF-8');
        $arr = json_decode($data, true);
        $arr['state'] = Wizard::app_state($package_name);
        Response::json($arr);
    }

    public function downloadRequest($package_name)
    {
        $app = AppModel::fetch_by_package_name($package_name);
        if (!empty($app))
            Response::json(rlang('manager.currently_installed'), false);

        $auth = Request::inputOne('auth');
        $params = $this->getAuthParams($auth);

        $res = Request::sendPost('https://www.pinoox.com/api/manager/v1/market/downloadRequest/' . $package_name, $params);
        if (!empty($res)) {
            $response = json_decode($res, true);
            if (!$response['status']) {
                exit($res);
            } else {
                $path = path("downloads>apps>" . $package_name . ".pin");
                Download::fetch('https://www.pinoox.com/api/manager/v1/market/download/' . $response['result']['hash'], $path)->process();
                Config::set('market.' . $package_name, $response['result']);
                Config::save('market');
                Response::json(rlang('manager.download_completed'), true);
            }
        }
    }

    /*-----------------------------------------------------------
    * Templates
    */

    public function getTemplates($package_name)
    {
        $data = Request::sendGet('https://www.pinoox.com/api/manager/v1/market/getAppTemplates/' . $package_name);
        HelperHeader::contentType('application/json', 'UTF-8');
        $result = json_decode($data, true);
        $templates = [];
        if (!empty($result)) {
            foreach ($result as $t) {
                //check template state
                $t['state'] = Wizard::template_state($package_name, $t['uid']);
                $t['type'] = 'theme';
                $templates[] = $t;
            }
        }

        Response::json($templates);
    }


    public function downloadRequestTemplate($uid)
    {
        $data = Request::input('auth,package_name', null, '!empty');
        $params = $this->getAuthParams($data['auth']);

        if (!Wizard::is_installed($data['package_name']))
            exit();

        $res = Request::sendPost('https://www.pinoox.com/api/manager/v1/market/downloadRequestTemplate/' . $uid, $params);
        if (!empty($res)) {
            $response = json_decode($res, true);
            if (!isset($response['status']) || !$response['status']) {
                exit($res);
            } else {
                $path = path("downloads>templates>$uid.pin");
                Download::fetch('https://www.pinoox.com/api/manager/v1/market/downloadTemplate/' . $response['result']['hash'], $path)->process();
                Response::json(rlang('manager.download_completed'), true);
            }
        }
    }

}
