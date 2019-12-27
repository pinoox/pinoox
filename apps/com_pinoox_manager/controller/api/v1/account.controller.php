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

use pinoox\component\Download;
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
        $postData = http_build_query($form);

        $http = [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n"
                . "Content-Length: " . strlen($postData) . "\r\n" .
                "Access-Control-Allow-Headers: *\r\n" . "Access-Control-Allow-origin: *\r\n",
            'content' => $postData
        ];
        $data = Download::fetch('https://www.pinoox.com/api/manager/v1/account/login')->timeout(8)->http($http)->process();
        exit($data);
    }

}