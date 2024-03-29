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

namespace App\com_pinoox_manager\Controller\api\v1;

use Pinoox\Component\Config;
use Pinoox\Component\Date;
use Pinoox\Component\Lang;
use Pinoox\Component\app\AppProvider;
use Pinoox\Component\Request;
use Pinoox\Component\Response;
use Pinoox\Component\Uploader;
use Pinoox\Portal\Url;
use Pinoox\Component\User;
use Pinoox\Component\Validation;
use Pinoox\Model\PinooxDatabase;
use Pinoox\Model\UserModel;

class UserController extends MasterConfiguration
{
    public function get()
    {
        if (User::isLoggedIn()) {
            $user = $this->getUser();
            Response::json($user, true);
        }

        Response::json([], false);
    }

    private function getUser()
    {
        $user = User::get();
        $isLock = User::getTokenData('isLock');
        if ($isLock)
            return [
                'isLock' => true,
                'full_name' => $user['full_name'],
                'avatar' => Url::upload($user['avatar_id'], Url::file('resources/avatar.png')),
                'avatar_thumb' => Url::thumb($user['avatar_id'], 128, Url::file('resources/avatar.png')),
            ];

        $user = User::get();
        return [
            'avatar' => Url::upload($user['avatar_id'], Url::file('resources/avatar.png')),
            'avatar_thumb' => Url::thumb($user['avatar_id'], 128, Url::file('resources/avatar.png')),
            'isAvatar' => !empty($user['avatar_id']),
            'fname' => $user['fname'],
            'lname' => $user['lname'],
            'full_name' => $user['full_name'],
            'username' => $user['username'],
            'email' => $user['email'],
        ];
    }

    public function login()
    {
        if (User::isLoggedIn()) {
            $isLock = User::getTokenData('isLock');
            if ($isLock) {
                $this->checkLock();
            } else {
                $this->error();
            }
        }

        $input = Request::input('username,password', null, '!empty');


        if (User::login($input['username'], $input['password'])) {
            $user = $this->getUser();
            Response::json($user, true);
        }
        Response::json(User::getMessage(), false);
    }

    private function checkLock()
    {
        $user_id = User::get('user_id');
        $password = Request::inputOne('password', null, '!empty');
        if (UserModel::fetch_by_password($user_id, $password)) {
            User::append('isLock', false);
            $user = $this->getUser();
            Response::json($user, true);
        }

        Response::json(t('~user.password_is_wrong'), false);
    }

    public function logout()
    {
        User::logout(null, false);
        Response::json(null, true);
    }

    public function lock()
    {
        if (User::isLoggedIn())
            User::append('isLock', true);

        $user = $this->getUser();
        Response::json($user, true);
    }

    public function deleteAvatar()
    {
        if (!User::isLoggedIn())
            $this->error();
        $user = User::get();
        Uploader::init()->thumb('128f', PINOOX_PATH_THUMB)->actRemoveRow($user['avatar_id']);
        if (UserModel::update_avatar($user['user_id'], null)) {
            $user = $this->getUser();
            Response::json($user, true);
        }

        Response::json(null, false);

    }

    public function changeAvatar()
    {
        if (!User::isLoggedIn())
            $this->error();

        if (Request::isFile('avatar')) {

            PinooxDatabase::startTransaction();

            $old_avatar_id = User::get('avatar_id');
            $up = Uploader::init('avatar', path('uploads/avatar/'))
                ->allowedTypes('png,jpg,jpeg', 2)
                ->changeName('time')
                ->transaction()
                ->thumb('128f', PINOOX_PATH_THUMB)
                ->insert('none', 'avatar')->finish(true);

            $avatar_id = $up->getInsertId();
            if ($up->isCommit() && UserModel::update_avatar(User::get('user_id'), $avatar_id)) {
                PinooxDatabase::commit();
                $up->commit();
                Uploader::init()->thumb('128f', PINOOX_PATH_THUMB)->actRemoveRow($old_avatar_id);
            }
            if ($result = $up->result()) {
                Response::json([
                    'avatar' => Url::upload($result),
                    'avatar_thumb' => Url::thumb($result),
                ], true);
            } else {
                Response::json($up->error('first'), false);
            }
        }

        Response::json(t('manager.invalid_request'), false);
    }

    public function changeInfo()
    {
        if (!User::isLoggedIn())
            $this->error();

        $inputs = Request::input('fname,lname,username,email', null, '!empty');

        $valid = Validation::check($inputs, [
            'fname' => ['length:>2', t('user.first_name')],
            'lname' => ['required|length:>2', t('user.last_name')],
            'username' => ['required|username', t('user.username')],
            'email' => ['required|email', t('user.email')],
        ]);
        if ($valid->isFail())
            Response::json($valid->first(), false);

        $user_id = User::get('user_id');
        if (UserModel::update_info($user_id, $inputs)) {
            Response::json(null, true);
        }

        Response::json(null, false);
    }

    public function changePassword()
    {
        if (!User::isLoggedIn())
            $this->error();

        $inputs = Request::input('old_password,new_password,valid_password', null, '!empty');

        $valid = Validation::check($inputs, [
            'old_password' => ['required', t('user.old_password')],
            'new_password' => ['required|length:5>|match:!=[old_password]', t('user.new_password')],
            'valid_password' => ['required|match:==[new_password]', t('user.valid_password')],
        ]);

        if ($valid->isFail())
            Response::json($valid->first(), false);

        $user_id = User::get('user_id');

        if (!UserModel::fetch_by_password($user_id, $inputs['old_password'])) {
            Response::json(t('user.err_old_password'), false);
        }

        if (UserModel::update_password($user_id, $inputs['new_password'], $inputs['old_password'])) {
            Response::json(null, true);
        }

        Response::json(t('user.err_old_password'), false);
    }

    public function getUsers($packageName)
    {
        UserModel::where_app($packageName);
        $users = UserModel::fetch_all(null, false, false);
        $users = array_map(function ($user) {
            return [
                'email' => $user['email'],
                'app' => $user['app'],
                'register_date_fa' => Date::j('Y/m/d', $user['register_date']),
                'fname' => $user['fname'],
                'lname' => $user['lname'],
                'status_fa' => t('user.' . $user['status']),
                'full_name' => $user['fname'] . ' ' . $user['lname'],
                'avatar' => Url::upload($user['avatar_id'], Url::file('resources/avatar.png')),
                'avatar_thumb' => Url::thumb($user['avatar_id'], 128, Url::file('resources/avatar.png')),
            ];
        }, $users);

        Response::json($users);
    }

    public function getOptions()
    {
        $options = Config::get('options');
        $options['lang'] = AppProvider::get('lang');
        Response::json($options);
    }
}
