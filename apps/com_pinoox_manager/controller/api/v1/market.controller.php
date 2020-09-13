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
use pinoox\component\Download;
use pinoox\component\HelperHeader;
use pinoox\component\Request;
use pinoox\component\Response;
use pinoox\component\Url;

class MarketController extends MasterConfiguration
{
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

        //check app state
        $arr['state'] = 'download';
        if (Wizard::is_installed($package_name))
            $arr['state'] = 'installed';
        else if (Wizard::is_downloaded($package_name))
            $arr['state'] = 'install';

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
                if (Wizard::is_installed_template($package_name, $t['uid']))
                    $t['state'] = 'installed';
                else if (Wizard::is_downloaded_template($t['uid']))
                    $t['state'] = 'install';
                else
                    $t['state'] = 'download';

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
            if (!$response['status']) {
                exit($res);
            } else {
                $path = path("downloads>templates>$uid.pin");
                Download::fetch('https://www.pinoox.com/api/manager/v1/market/downloadTemplate/' . $response['result']['hash'], $path)->process();
                Response::json(rlang('manager.download_completed'), true);
            }
        }
    }

}
