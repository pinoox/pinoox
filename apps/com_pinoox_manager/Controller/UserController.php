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
use Pinoox\Model\FileModel;
use Pinoox\Model\UserModel;
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\FileUploader;
use Pinoox\Portal\Hash;
use Pinoox\Portal\Url;

class UserController extends Api
{
    public function deleteAvatar()
    {
        $user = User::get();
        DB::beginTransaction();
        FileModel::where('file_id', $user['avatar_id'])->delete();
        if (UserModel::where('user_id', $user['user_id'])->update(['avatar_id' => null])) {
            DB::commit();
            $user = self::getDataUser();
            return $this->message($user);
        }
        DB::rollBack();
        return $this->message(null, false);
    }

    public function changeAvatar(Request $request)
    {
        if ($request->files->has('avatar')) {

            $validation = $request->validation([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            if ($validation->fails())
                return $this->message($validation->errors()->first(), false);

            $oldAvatarId = User::get('avatar_id');
            $up = FileUploader::store('uploads/avatar/', 'avatar')
                ->group('avatar')
                ->thumb()
                ->insert()
                ->upload();

            $avatar_id = $up->getResult('file_id');
            UserModel::where('user_id', User::get('user_id'))
                ->update([
                    'avatar_id' => $avatar_id,
                ]);

            if (!empty($oldAvatarId))
                FileModel::where('file_id', $oldAvatarId)->delete();
            $file = FileModel::where('file_id', $avatar_id)->first();

            return $this->message([
                'avatar' => $file->file_link,
                'avatar_thumb' => $file->thumb_link,
            ]);
        }

        return $this->message(t('manager.invalid_request'), false);
    }

    public function changeInfo(Request $request)
    {
        $validation = $request->validation([
            'fname' => 'required|min:3',
            'lname' => 'required|min:3',
            'email' => 'required|email',
            'username' => 'required|alpha_dash:ascii|min:3',
        ]);

        if ($validation->fails())
            return $this->message($validation->errors()->first(), false);

        $inputs = $validation->validate();

        $user_id = User::get('user_id');

        UserModel::where('user_id', $user_id)->update($inputs);
        return $this->message(null);
    }

    public function changePassword(Request $request)
    {
        $validation = $request->validation([
            'old_password' => 'required',
            'new_password' => 'required|min:5|different:old_password',
            'valid_password' => 'required|same:new_password',
        ]);

        if ($validation->fails())
            return $this->message($validation->errors()->first(), false);

        $inputs = $validation->validate();
        $user_id = User::get('user_id');

        $user = UserModel::where('user_id', $user_id)->first();

        if (!Hash::check($inputs['old_password'], $user['password'])) {
            return $this->message(t('user.err_old_password'), false);
        }

        UserModel::updatePassword($user_id, $inputs['new_password']);
        return $this->message(null);
    }

    public static function getDataUser()
    {
        $user_id = User::get('user_id');
        $user = UserModel::where('user_id', $user_id)->first();
        $isLock = User::getTokenData('isLock');
        $default = Url::path('resources/avatar.png');
        $isAvatar = !empty($user->avatar_id);
        $avatar = $avatarThumb = $default;
        if ($user->file) {
            $avatar = Url::check($user->file->file_link, $default);
            $avatarThumb = Url::check($user->file->thumb_link, $default);
        }

        if ($isLock) {
            return [
                'isLock' => true,
                'full_name' => $user->full_name,
                'avatar' => $avatar,
                'avatar_thumb' => $avatarThumb,
                'isAvatar' => $isAvatar,
            ];
        }

        return [
            'avatar' => $avatar,
            'avatar_thumb' => $avatarThumb,
            'isAvatar' => $isAvatar,
            'fname' => $user->fname,
            'lname' => $user->lname,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'email' => $user->email,
        ];
    }

}