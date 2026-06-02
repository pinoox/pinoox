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
use Pinoox\Component\User;
use Pinoox\Model\UserModel;
use Pinoox\Portal\Hash;

class AuthController extends Api
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
        $user?->makeVisible('password');

        if (!$user || !Hash::check($input['password'], $user->password))
            return $this->error(t('user.username_or_password_is_wrong'));

        User::type(User::JWT);
        User::setUserSessionKey('manager_pinoox');
        User::setToken($user);

        return $this->message(t('user.logged_in_successfully'), User::$login_key);
    }

    public function get()
    {
        if (User::isLoggedIn())
            return User::get();

        return $this->error(t('user.you_must_login'), 401);
    }

    public function logout()
    {
        User::logout();

        return $this->message('logout');
    }

    public function lock()
    {
        if (User::isLoggedIn())
            User::append('isLock', true);

        return $this->message(UserController::getDataUser());
    }
}
