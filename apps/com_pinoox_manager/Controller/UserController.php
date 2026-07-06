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
use Pinoox\Portal\Config;
use Pinoox\Portal\Auth;

class UserController extends ApiController
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
            return $this->deny('user.avatar_delete_failed');
        }

        return $this->message('user.avatar_deleted_successfully', $profile);
    }

    public function changeAvatar(Request $request)
    {
        if (!$request->files->has('avatar')) {
            return $this->deny('manager.invalid_request');
        }

        $this->validated($request, [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $result = Auth::changeAvatar(Auth::id(), $request->files->get('avatar'));

        if ($result === false) {
            return $this->deny('manager.invalid_request');
        }

        return $this->message('user.avatar_changed_successfully', $result);
    }

    public function changeInfo(Request $request)
    {
        $input = $this->validated($request, Auth::profileRules(Auth::id()));

        Auth::updateProfile(Auth::id(), $input);

        return $this->message('user.profile_updated_successfully');
    }

    public function changePassword(Request $request)
    {
        $inputs = $this->validated($request, Auth::passwordRules());

        $result = Auth::changePassword(Auth::id(), $inputs['old_password'], $inputs['new_password']);

        if ($result !== true) {
            $message = is_string($result) ? $result : 'user.err_old_password';

            return $this->deny($message);
        }

        return $this->message('user.password_changed_successfully');
    }

    public static function getDataUser()
    {
        return Auth::profile();
    }
}
