<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       pinoox.com
 * @copyright  pinoox
 */


namespace App\com_pinoox_manager\Controller\api\v1;

use Pinoox\Portal\Config;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\HttpRequest;
use Pinoox\Component\Request as RequestData;
use Pinoox\Portal\Url;
use Pinoox\Component\Validation;

class AccountController extends LoginConfiguration
{


    public function getPinooxAuth()
    {
        $token_key = Config::get('connect.token_key');
        $data = RequestData::sendPost(
            'https://www.pinoox.com/api/manager/v1/account/getData',
            [
                'remote_url' => Url::site(),
                'token_key' => $token_key,
            ],
            [
                'type' => HttpRequest::form,
                'timeout' => 8000
            ]
        );

        $data = Str::decodeJson($data);
        if ($data['status']) {
            Response::json($data['result']);
        }

        return null;
    }

    public function connect()
    {
        $data = RequestData::sendPost(
            'https://www.pinoox.com/api/manager/v1/account/getToken',
            [
                'remote_url' => Url::site(),
            ],
            [
                'type' => HttpRequest::form,
                'timeout' => 8000
            ]
        );

        $array = json_decode($data, true);
        if (!empty($array['token_key'])) {
            Config::name('connect')
                ->set('token_key', $array['token_key'])
                ->save();

            return $this->message($array['token_key'], true);
        }

        return $this->message(null, false);
    }

    public function getConnectData()
    {
        return Config::name('connect')->get();
    }

    public function logout()
    {
        Config::name('connect')
            ->set('token_key', null)
            ->save();
    }
}