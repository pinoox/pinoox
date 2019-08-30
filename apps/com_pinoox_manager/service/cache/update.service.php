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
namespace pinoox\app\com_pinoox_manager\service\cache;

use pinoox\component\Cache;
use pinoox\component\Config;
use pinoox\component\Download;
use pinoox\component\HelperString;
use pinoox\component\interfaces\ServiceInterface;
use pinoox\component\Url;

class UpdateService implements ServiceInterface
{

    public function _run()
    {
        Cache::init('version',function () {

            $pinoox = Config::get('~pinoox');
            $data = array('domain' => Url::domain(), 'site' => Url::site(), 'app' => Url::app(), 'version_name' => $pinoox['version_name'], 'version_code' => $pinoox['version_code'], 'php' => phpversion());
            $data = http_build_query($data);
            $http = [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n"
                    . "Content-Length: " . strlen($data) . "\r\n" .
                    "Access-Control-Allow-Headers: *\r\n" . "Access-Control-Allow-origin: *\r\n",
                'content' => $data
            ];
            $data = Download::fetch('https://www.pinoox.com/api/v1/update/checkVersion/')->timeout(8)->http($http)->process();
            return HelperString::decodeJson($data);
        },(5*24));
    }

    public function _stop()
    {
    }
}
    
