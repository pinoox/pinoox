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
use Pinoox\Portal\Config;
use Pinoox\Portal\Auth;

class UserController extends Api
{
    public function get()
    {
        return self::getDataUser();
    }

    public function getOptions()
    {
        $options = Config::name('options')->get() ?? [];
        $options['lang'] = app()->lang();

        return $options;
    }

    public function getUsers($packageName)
    {
        return Auth::listForApp($packageName);
    }

    public function deleteAvatar()
    {
        $profile = Auth::removeAvatar(Auth::id());

        if ($profile === null) {
            return $this->message(null, false);
        }

        return $this->message($profile);
    }

    public function changeAvatar(Request $request)
    {
        if (!$request->files->has('avatar')) {
            return $this->message(t('manager.invalid_request'), false);
        }

        $this->validated($request, [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $result = Auth::changeAvatar(Auth::id(), $request->files->get('avatar'));

        if ($result === false) {
            return $this->message(t('manager.invalid_request'), false);
        }

        return $this->message($result);
    }

    public function changeInfo(Request $request)
    {
        $input = $this->validated($request, Auth::profileRules(Auth::id()));

        Auth::updateProfile(Auth::id(), $input);

        return $this->message(null);
    }

    public function changePassword(Request $request)
    {
        $inputs = $this->validated($request, Auth::passwordRules());

        $result = Auth::changePassword(Auth::id(), $inputs['old_password'], $inputs['new_password']);

        if ($result !== true) {
            return $this->message(is_string($result) ? $result : t('user.err_old_password'), false);
        }

        return $this->message(null);
    }

    public static function getDataUser()
    {
        return Auth::profile();
    }
}

