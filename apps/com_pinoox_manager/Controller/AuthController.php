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
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Portal\Auth;

class AuthController extends ApiController
{
    public function login(Request $request)
    {
        Auth::boot();

        if (!Auth::guest()) {
            return $this->error('user.already_logged_in', 401);
        }

        $input = $this->validated($request, [
            'username' => 'required',
            'password' => 'required',
        ]);

        $remember = (bool) ($input['remember'] ?? false);

        $result = Auth::attemptResult([
            'username' => $input['username'],
            'password' => $input['password'],
        ], $remember);

        if (!$result->success) {
            return $this->error($result->message ?? 'user.username_or_password_is_wrong');
        }

        return $this->message('user.logged_in_successfully', $result->token);
    }

    public function get()
    {
        if (Auth::check()) {
            return Auth::get();
        }

        return $this->error('user.you_must_login', 401);
    }

    public function logout()
    {
        Auth::logout();

        return $this->message('manager.logout');
    }

    public function lock()
    {
        return $this->ok(Auth::lock());
    }

    public function unlock(Request $request)
    {
        $input = $this->validated($request, [
            'password' => 'required',
        ]);

        $result = Auth::unlock($input['password']);

        if ($result !== true) {
            return $this->error(is_string($result) ? $result : 'user.password_is_wrong');
        }

        return $this->message('manager.unlocked_successfully', Auth::profile());
    }
}
