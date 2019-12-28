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
use pinoox\component\HttpRequest;
use pinoox\component\interfaces\ServiceInterface;
use pinoox\component\Request;
use pinoox\component\Url;

class UpdateService implements ServiceInterface
{

    public function _run()
    {
        Cache::init('version',function () {

            $pinoox = Config::get('~pinoox');
            $data = Request::sendPost(
                'https://www.pinoox.com/api/v1/update/checkVersion/',
                [
                    'domain' => Url::domain(),
                    'site' => Url::site(), 'app' => Url::app(),
                    'version_name' => $pinoox['version_name'],
                    'version_code' => $pinoox['version_code'],
                    'php' => phpversion()
                ],
                [
                    'timeout' => 8000,
                    'type' => HttpRequest::form,
                ]
            );

            return HelperString::decodeJson($data);
        },(5*24));
    }

    public function _stop()
    {
    }
}
    
