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

use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\app\AppProvider;
use pinoox\component\Cache;
use pinoox\component\Config;
use pinoox\component\Download;
use pinoox\component\File;
use pinoox\component\HelperHeader;
use pinoox\component\Lang;
use pinoox\component\Request;
use pinoox\component\Response;
use pinoox\component\Router;
use pinoox\component\Service;
use pinoox\component\Url;
use pinoox\component\User;
use pinoox\component\Validation;
use pinoox\component\Zip;
use pinoox\model\PinooxDatabase;
use pinoox\model\UserModel;

class MarketController extends MasterConfiguration
{
    public function getApps($keyword = '')
    {
        $data = file_get_contents('https://www.pinoox.com/api/manager/v1/market/get/' . $keyword);
        //  $data = Download::fetch('https://www.pinoox.com/api/v1/market/' . $keyword)->process();
        HelperHeader::contentType('application/json', 'UTF-8');
        echo $data;
    }

    public function getOneApp($package_name)
    {
        $data = file_get_contents("https://www.pinoox.com/api/manager/v1/market/getApp/$package_name");
        HelperHeader::contentType('application/json', 'UTF-8');
        echo $data;
    }

    public function downloadRequest($package_name)
    {
        $auth = Request::inputOne('auth');
        $app = AppModel::fetch_by_package_name($package_name);
        if (!empty($app))
            Response::json(rlang('manager.currently_installed'), false);

        $pinVer = Config::get('~pinoox');
        $params = [
            'token' => $auth['token'],
            'remote_url' => Url::site(),
            'user_agent' => HelperHeader::getUserAgent() . ';Pinoox/' . $pinVer['version_name'] . ' Manager',
        ];
        $res = Request::sendPost('https://www.pinoox.com/api/manager/v1/market/downloadRequest/' . $package_name, $params);
        if (!empty($res)) {
            $response = json_decode($res, true);
            if (!$response['status']) {
                exit($res);
            } else {
                $path = path("downloads>apps>" . $package_name . ">" . $package_name . ".pin");
                Download::fetch('https://www.pinoox.com/api/manager/v1/market/download/' . $response['result'], $path)->process();
            }
        }
    }
}
