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

namespace App\com_pinoox_manager\Controller;

use Pinoox\Component\Http\Request;
use Pinoox\Component\HttpRequest;
use Pinoox\Component\User;
use Pinoox\Portal\Config;
use Pinoox\Portal\Url;

class AuthController extends ApiController
{
    public function login(Request $request)
    {
        $validation = $request->validation([
            'username' => 'required',
            'password' => 'required',
        ]);


        if ($validation->fails())
            return $this->message($validation->errors()->first(), false);

        $input = $validation->validate();

        if (User::login($input['username'], $input['password'])) {
            return $this->message(User::get(), true);
        }

        return $this->message(rlang('validation.username_or_password_is_wrong'), false);
    }

}