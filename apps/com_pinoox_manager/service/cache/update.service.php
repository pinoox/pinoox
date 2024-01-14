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
namespace App\com_pinoox_manager\Service\cache;

use Pinoox\Component\Cache;
use Pinoox\Component\Config;
use Pinoox\Component\Download;
use Pinoox\Component\HelperString;
use Pinoox\Component\HttpRequest;
use Pinoox\Component\Interfaces\ServiceInterface;
use Pinoox\Component\Request;
use Pinoox\Component\Url;

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

            return Str::decodeJson($data);
        },(5*24));
    }

    public function _stop()
    {
    }
}
    
