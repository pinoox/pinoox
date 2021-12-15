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

namespace pinoox\model;

use pinoox\component\User;
use pinoox\component\Date;
use pinoox\component\MagicTrait;
use pinoox\component\Security;

class UserModel extends PinooxDatabase
{

    const active = "active";
    const suspend = "suspend";
    const pending = "pending";

    use MagicTrait;

    public static function checkApp($app = null)
    {
        $app = is_null($app) ? User::getApp() : $app;
        if (!empty($app))
            self::$db->where('app', $app);

    }

    public static function fetch_by_id($user_id, $isCustomSelect = true)
    {
        $selected = $isCustomSelect ? 'u.*,CONCAT(u.fname," ",u.lname) full_name' : '';

        self::checkApp();
        self::$db->where('user_id', $user_id);
        return self::$db->getOne(self::user . ' u', $selected);
    }

    public static function get_app($user_id)
    {
        self::$db->where('user_id', $user_id);
        return self::$db->getOne(self::user, 'app');
    }


    public static function fetch_user_by_email($email, $notUser = null)
    {
        self::checkApp();
        if (!empty($notUser))
            self::$db->where('user_id', $notUser, '!=');

        self::$db->where('email', $email);
        return self::$db->getOne(self::user);
    }

    public static function fetch_user_by_email_or_username($username, $notUser = null , $app = null)
    {
        self::checkApp($app);
        if (!empty($notUser))
            self::$db->where('user_id', $notUser, '!=');

        self::$db->where('(email = ? OR username = ?)', [$username, $username]);
        return self::$db->getOne(self::user);
    }

    public static function update_session($user_id, $session_id)
    {
        self::checkApp();
        self::$db->where('user_id', $user_id);
        return self::$db->update(self::user, array(
            'session_id' => $session_id
        ));
    }

    public static function update_status($user_id, $status)
    {
        self::checkApp();
        self::$db->where('user_id', $user_id);
        return self::$db->update(self::user, array(
            'status' => $status
        ));
    }

    public static function update_avatar($user_id, $avatar_id)
    {
        self::checkApp();
        self::$db->where('user_id', $user_id);
        return self::$db->update(self::user, array(
            'avatar_id' => $avatar_id
        ));
    }

    public static function fetch_all($limit = null, $isCount = false, $checkApp = true)
    {
        if ($checkApp)
            self::checkApp();
        $result = self::$db->get(self::user, $limit);
        if ($isCount) return count($result);
        return $result;
    }

    public static function count()
    {
        return self::fetch_all(null, true);
    }

    public static function insert($data)
    {
        return self::$db->insert(self::user, [
            'app' => isset($data['app']) ? $data['app'] : User::getApp(),
            'fname' => isset($data['fname']) ? $data['fname'] : null,
            'lname' => isset($data['lname']) ? $data['lname'] : null,
            'email' => $data['email'],
            'username' => isset($data['username']) ? $data['username'] : null,
            'password' => Security::passHash($data['password']),
            'register_date' => Date::g('Y-m-d H:i:s'),
            'status' => isset($data['status']) ? $data['status'] : self::active,
        ]);
    }

    public static function copy($user_id, $app)
    {
        $user = self::fetch_by_id($user_id, false);
        if (!empty($user)) {

            $user['app'] = $app;
            $user['register_date'] = Date::g('Y-m-d H:i:s');
            $user['status'] = self::active;
            unset($user['session_id']);
            unset($user['avatar_id']);
            unset($user['user_id']);
            return self::$db->insert(self::user, $user);
        }
        return false;
    }

    public static function update($user_id, $data)
    {
        self::checkApp();

        if (!empty($data['password']))
            $data['password'] = Security::passHash($data['password']);
        $formData = [
            'avatar_id' => $data['avatar_id'],
            'fname' => $data['fname'],
            'lname' => $data['lname'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => $data['status'],
        ];
        if (empty($data['password']))
            unset($formData['password']);
        self::$db->where('user_id', $user_id);
        return self::$db->update(self::user, $formData);
    }

    public static function update_info($user_id, $data)
    {
        self::checkApp();

        $formData = [
            'fname' => $data['fname'],
            'lname' => $data['lname'],
            'email' => $data['email'],
            'username' => $data['username'],
        ];
        self::$db->where('user_id', $user_id);
        return self::$db->update(self::user, $formData);
    }

    public static function update_password($user_id, $newPassword, $oldPassword = null , $app = null )
    {
        self::checkApp($app);

        if (!is_null($oldPassword))
            self::$db->where('password', Security::passHash($oldPassword));
        self::$db->where('user_id', $user_id);
        return self::$db->update(self::user, [
            'password' => Security::passHash($newPassword),
        ]);
    }

    public static function fetch_by_password($user_id, $password)
    {
        self::$db->where('password', Security::passHash($password));
        self::$db->where('user_id', $user_id);
        return self::$db->getOne(self::user);
    }

    public static function fetch_by_username($username, $no_user_id = null)
    {
        self::checkApp();
        if (!empty($no_user_id))
            self::$db->where('user_id', $no_user_id, ' != ');
        self::$db->where('username', $username);
        return self::$db->getOne(self::user, '*,CONCAT(fname," ",lname) full_name');
    }

    public static function delete($user_id)
    {
        self::checkApp();
        self::$db->where('user_id', $user_id);
        return self::$db->delete(self::user);
    }

    public static function delete_by_app($app)
    {
        self::checkApp($app);
        return self::$db->delete(self::user);
    }

    public static function where_status($status)
    {
        self::$db->where('status', $status);
    }

    public static function where_app($package_name)
    {
        self::$db->where('app', $package_name);
    }

    public static function where_search($keyword)
    {
        if (empty($keyword)) return;
        if ($keyword == rlang('panel.active')) $status = self::active;
        else if ($keyword == rlang('panel.suspend')) $status = self::suspend;
        else $status = false;

        $k = '%' . $keyword . '%';
        if ($status !== false)
            self::$db->where('(`status` LIKE ? )', [$status]);
        else
            self::$db->where('(fname LIKE ? OR lname LIKE ? OR email LIKE ? )', [$k, $k, $k]);
    }

    public static function fetch_stats()
    {
        $total = self::fetch_all(null, true);

        self::where_status(self::active);
        $actives = self::fetch_all(null, true);

        self::where_status(self::suspend);
        $suspends = self::fetch_all(null, true);

        return [
            'total' => $total,
            'actives' => $actives,
            'suspends' => $suspends
        ];
    }
}
    
