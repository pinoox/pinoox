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

namespace pinoox\component;

use pinoox\model\UserModel;

class User
{
    private static $key = 'pin';
    private static $user = null;
    private static $app = null;
    private static $msg = null;

    public function __construct($name = null)
    {
        self::init($name);
    }

    public static function init($name = null)
    {
        if (!empty($name)) self::key($name);
    }

    public static function key($name)
    {
        self::$key = $name;
    }

    public static function app($package_name)
    {
        self::$app = $package_name;
    }

    public static function getApp()
    {
        return (!empty(self::$app)) ? self::$app : Router::getApp();
    }

    public static function append($key, $val = null)
    {
        $user = self::getSession();
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $user[$k] = $v;
            }
        } else {
            $user[$key] = $val;
        }

        self::set($user);
    }

    public static function get($field = null)
    {
        $user_id = Session::get(self::getKey(), 'user_id');
        if ($user_id && empty(self::$user)) {
            $user = UserModel::fetch_by_id($user_id);
            if ($user && $user['status'] == 'active') {
                if (isset($user['password'])) unset($user['password']);
                self::$user = $user;
            } else {
                self::logout(null, false);
            }
        }

        if (!empty($field)) {
            return (isset(self::$user[$field])) ? self::$user[$field] : null;
        } else {
            return self::$user;
        }
    }

    public static function getKey()
    {
        return 'user_' . self::$key;
    }

    public static function logout($link = null, $isRedirect = true, $rawLink = false)
    {
        if (self::isLoggedIn()) {

            Session::remove(self::getKey());
            if (Session::has())
                Session::regenerateId(true);

            //regenerate session id for preventing fixation attack
            if ($rawLink)
                Response::redirect($link);
            else if ($isRedirect && isset($_SERVER['HTTP_REFERER']))
                Response::redirect($_SERVER['HTTP_REFERER']);
        } else {
            if ($rawLink)
                Response::redirect($link);

            if (empty($link))
                $link = 'account/login';
            if ($isRedirect) Response::redirect(Url::app() . $link);
        }

    }

    public static function isLoggedIn()
    {
        return Session::has(self::getKey());
    }

    public static function set($user)
    {
        Session::set(self::getKey(), $user);
    }

    public static function getSession($field = null)
    {
        $user = Session::get(self::getKey());

        if (!empty($field)) {
            return (isset($user[$field])) ? $user[$field] : null;
        } else {
            return $user;
        }
    }

    public static function login($username, $password)
    {
        self::$msg = null;
        UserModel::where_status(UserModel::active);
        $user = UserModel::fetch_user_by_email_or_username($username);
        if (empty($user)) {
            self::$msg = Lang::get('~user.username_or_password_is_wrong');
            return false;
        }

        if (!Security::passVerify($password, $user['password'])) {
            self::$msg = Lang::get('~user.username_or_password_is_wrong');
            return false;
        }

        self::setAuto($user);
        return true;

    }

    public static function setAuto($user)
    {
        if (empty($user)) return;
        if (isset($user['password'])) unset($user['password']);
        Session::set(self::getKey(), $user);
        UserModel::update_session($user['user_id'], Session::getSessionId());
    }

    public static function getMessage()
    {
        return self::$msg;
    }

    public static function remove($key)
    {
        $user = self::get();
        if (is_array($key)) {
            foreach ($key as $k) {
                if (isset($user[$k]))
                    unset($user[$k]);
            }
        } else {
            if (isset($user[$key]))
                unset($user[$key]);
        }

        self::set($user);
    }

    public static function reset()
    {
        $user = UserModel::fetch_by_id(self::get('user_id'));
        self::setAuto($user);
    }
}
    
