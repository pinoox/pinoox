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
use Pinoox\Component\Token;
use Pinoox\Component\User;
use Pinoox\Model\UserModel;
use Pinoox\Portal\Hash;

class AuthController extends ApiController
{
    public function login(Request $request)
    {
        if (User::isLoggedIn())
            return $this->error(t('user.already_logged_in'), 401);

        $validation = $request->validation([
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validation->fails())
            return $this->error($validation->errors()->first());

        $input = $validation->validate();

        $user = UserModel::where('username', $input['username'])->first();
        $u = $user?->makeVisible('password');
        if (!$u || !Hash::check($input['password'], $u->password)) {
            return $this->error(t('user.username_or_password_is_wrong'));
        }

        $userToken = UserModel::where('user_id', $user->user_id)->first();

        if ($userToken) {
            User::setUserSessionKey('pinoox_manager');
            User::setToken($userToken);
            return $this->message(t('user.logged_in_successfully'), User::$login_key);
        } else {
            return $this->error(t('user.username_or_password_is_wrong'));
        }
    }

    public function get()
    {
        if (User::isLoggedIn()) {
            return User::get();
        } else {
            return $this->error(t('user.you_must_login'), 401);
        }
    }

    public function logout()
    {
        User::logout();

        return $this->message('logout');
    }
}