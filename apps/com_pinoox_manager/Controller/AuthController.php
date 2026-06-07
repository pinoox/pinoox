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
use Pinoox\Portal\Auth;

class AuthController extends Api
{
    public function login(Request $request)
    {
        Auth::boot();

        if (!Auth::guest()) {
            return $this->error(t('user.already_logged_in'), 401);
        }

        $validation = $request->validation([
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validation->fails()) {
            return $this->error($validation->errors()->first());
        }

        $input = $validation->validate();
        $remember = (bool) ($input['remember'] ?? false);

        $result = Auth::attemptResult([
            'username' => $input['username'],
            'password' => $input['password'],
        ], $remember);

        if (!$result->success) {
            return $this->error($result->message ?? t('user.username_or_password_is_wrong'));
        }

        return $this->message(t('user.logged_in_successfully'), $result->token);
    }

    public function get()
    {
        if (Auth::check()) {
            return Auth::get();
        }

        return $this->error(t('user.you_must_login'), 401);
    }

    public function logout()
    {
        Auth::logout();

        return $this->message('logout');
    }

    public function lock()
    {
        return $this->message(Auth::lock());
    }

    public function unlock(Request $request)
    {
        $validation = $request->validation([
            'password' => 'required',
        ]);

        if ($validation->fails()) {
            return $this->error($validation->errors()->first());
        }

        $result = Auth::unlock($validation->validate()['password']);

        if ($result !== true) {
            return $this->error(is_string($result) ? $result : t('user.password_is_wrong'));
        }

        return $this->message(Auth::profile());
    }
}
