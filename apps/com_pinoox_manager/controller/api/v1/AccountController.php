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


namespace pinoox\app\com_pinoox_manager\controller\api\v1;

use pinoox\portal\Config;
use pinoox\component\helpers\Str;
use pinoox\component\HttpRequest;
use pinoox\component\Request as RequestData;
use pinoox\component\Response;
use pinoox\component\Url;
use pinoox\component\Validation;

class AccountController extends LoginConfiguration
{

    public function login()
    {
        $form = RequestData::input('email,password', null, '!empty');

        $valid = Validation::check($form, [
            'email' => ['required', rlang('user.username_or_email')],
            'password' => ['required', rlang('user.password')],
        ]);

        if ($valid->isFail())
            Response::json($valid->first(), false);

        $form['remote_url'] = Url::site();

        $data = RequestData::sendPost(
            'https://www.pinoox.com/api/manager/v1/account/login',
            $form,
            [
                'type' => HttpRequest::form,
                'timeout' => 8000
            ]
        );
        $array = json_decode($data, true);
        if ($array['status']) {
            Config::name('connect')
                ->set('token_key', $array['result']['token'])
                ->save();
        }

        return $data;
    }

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