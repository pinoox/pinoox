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

use pinoox\component\Config;
use pinoox\component\HttpRequest;
use pinoox\component\Request;
use pinoox\component\Response;
use pinoox\component\Url;
use pinoox\component\Validation;

class AccountController extends MasterConfiguration
{

    public function login()
    {
        $form = Request::input('email,password', null, '!empty');

        $valid = Validation::check($form, [
            'email' => ['required', rlang('user.username_or_email')],
            'password' => ['required', rlang('user.password')],
        ]);

        if ($valid->isFail())
            Response::json($valid->first(), false);

        $form['remote_url'] = Url::site();

        $data = Request::sendPost(
            'https://www.pinoox.com/api/manager/v1/account/login',
            $form,
            [
                'type' => HttpRequest::form,
                'timeout' => 8000
            ]
        );
        $array = json_decode($data, true);
        if ($array['status']) {
            Config::set('options.pinoox_auth', $array['result']);
            Config::save('options');
        }
        exit($data);
    }

    public function getPinooxAuth()
    {
        Response::json(Config::get('options.pinoox_auth'));
    }

    public function logout()
    {
        Config::remove('options.pinoox_auth');
        Config::save('options');
    }
}