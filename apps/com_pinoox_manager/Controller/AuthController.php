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
use Pinoox\Portal\Config;
use Pinoox\Portal\Hash;
use Pinoox\Portal\Url;

class AuthController extends ApiController
{
    public function login(Request $request)
    {
        if (User::isLoggedIn()) {
            $isLock = User::getTokenData('isLock');
            if ($isLock) {
                return $this->checkLock($request);
            } else {
                $this->notFoundError();
            }
        }


        $validation = $request->validation([
            'username' => 'required',
            'password' => 'required',
        ]);


        if ($validation->fails())
            return $this->message($validation->errors()->first(), false);

        $input = $validation->validate();

        if (User::login($input['username'], $input['password'])) {
            $user = $this->getUser();
            return $this->message($user);
        }

        return $this->message(t('validation.username_or_password_is_wrong'), false);
    }


    private function checkLock(Request $request)
    {
        $user_id = User::get('user_id');
        $validation = $request->validation([
            'password' => 'required',
        ]);

        if ($validation->fails())
            return $this->message($validation->errors()->first(), false);

        $input = $validation->validate();
        $user = UserModel::where('user_id', $user_id)->first();
        if (Hash::check($input['password'], $user->password)) {
            User::append('isLock', false);
            $user = $this->getUser();
            return $this->message($user);
        }

        return $this->message(t('validation.username_or_password_is_wrong'), false);
    }

    public function getUser()
    {
        if (User::isLoggedIn()) {
            $user = UserController::getDataUser();
            return $this->message($user);
        }

        return $this->message([], false);
    }

    public function getOptions()
    {
        $options = config('options');
        $options['lang'] = app('lang');
        return $options;
    }


    public function logout()
    {
        User::logout();
        return $this->message(null);
    }

    public function lock()
    {
        if (User::isLoggedIn())
            User::append('isLock', true);

        $user = $this->getUser();
        return $this->message($user);
    }
}