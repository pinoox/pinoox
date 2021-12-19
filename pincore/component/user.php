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

use pinoox\model\TokenModel;
use pinoox\model\UserModel;

class User
{
    const COOKIE = 'cookie';
    const SESSION = 'session';
    const JWT = 'jwt';
    public static $login_key = null;
    private static $app = null;
    private static $msg = null;
    private static $type = self::COOKIE;
    private static $lifetime = 86400;
    private static $token = null;
    private static $token_key = null;
    private static $user = null;
    private static $updateLifetime = true;
    private static $updateTokenKey = false;
    private static $secret_key = 'BAF55D93DF7A2B3AA64722AA85448424AAB5CF4214AD2899CD9440BEC9B44894';

    public static function app($package_name)
    {
        self::$app = $package_name;
    }

    public static function updateLifetime($status)
    {
        self::$updateLifetime = $status;
    }

    public static function updateTokenKey($status)
    {
        self::$updateTokenKey = $status;
    }

    public static function type($value)
    {
        self::$type = $value;
        self::reset();
    }

    public static function reset()
    {
        self::$token = null;
        self::$user = null;
    }

    public static function getApp()
    {
        return (!empty(self::$app)) ? self::$app : Router::getApp();
    }

    public static function login($username, $password, $isActive = true)
    {
        self::$msg = null;

        if (self::isLoggedIn()) {
            self::$msg = Lang::get('~user.already_logged_in');;
            return false;
        }

        if ($isActive)
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

        self::setToken($user);
        return true;
    }

    public static function isLoggedIn()
    {
        $token = self::getToken();
        return !empty($token);
    }

    public static function getToken($field = null)
    {
        if (empty(self::$token)) {
            $token_key = self::getTokenKey();
            if ($token_key)
                self::$token = Token::get($token_key);
        }

        if (!empty($field)) {
            return (isset(self::$token[$field])) ? self::$token[$field] : null;
        } else {
            return self::$token;
        }
    }

    public static function getTokenKey()
    {
        if (!empty(self::$token_key)) return self::$token_key;
        switch (self::$type) {
            case self::COOKIE:
                self::$token_key = Cookie::get('pinoox_user');
                break;
            case self::JWT:
                self::$token_key = self::authToken();
                break;
            case self::SESSION:
                self::$token_key = Session::get('pinoox_user');
                break;
        }
        return self::$token_key;
    }

    public static function authToken($token = null)
    {
        if (is_null($token)) {
            $header = apache_request_headers();
            $token = @$header['Authorization'];
            $token = empty($token)? @$header['authorization'] : $token;
            if (empty($token))
                return false;
        }
        try {
            $payload = JWT::decode($token, self::$secret_key, array('HS256'));
            $token_key = $payload->pinoox_user;

            return $token_key;

        } catch (\Exception $e) {
        }

        return false;
    }

    public static function setToken($data)
    {
        if (isset($data['password']))
            unset($data['password']);
        $user_id = $data['user_id'];
        $token_key = self::getTokenKey();
        $token_key = Token::generate($data, 'pinoox_user', $user_id, $token_key);
        self::setClientToken($token_key);
    }

    private static function setClientToken($token_key)
    {
        self::$token_key = $token_key;
        self::$login_key = $token_key;

        switch (self::$type) {
            case self::COOKIE:
                Cookie::set('pinoox_user', $token_key, 999999999);
                break;
            case self::JWT:
                $payloadArray = [
                    'pinoox_user' => $token_key,
                ];
                self::$login_key = JWT::encode($payloadArray, self::$secret_key);
                break;
            case self::SESSION:
                Session::lifeTime(999999999);
                Session::set('pinoox_user', $token_key);
                break;
        }
    }

    public static function lifeTime($lifeTime, $unitTime = null)
    {
        Token::lifeTime($lifeTime, $unitTime);
    }

    public static function getTokenData($field = null)
    {
        $data = self::getToken('token_data');


        if (!empty($field)) {
            return (isset($data[$field])) ? $data[$field] : null;
        } else {
            return $data;
        }
    }

    public static function getMessage()
    {
        return self::$msg;
    }

    public static function get($field = null)
    {
        $token = self::getToken();
        $user_id = @$token['user_id'];
        if ($user_id && empty(self::$user)) {
            $user = UserModel::fetch_by_id($user_id);
            if ($user && $user['status'] == 'active') {
                if (isset($user['password'])) unset($user['password']);
                self::$user = $user;
                if (self::$updateTokenKey) {
                    $token_key = Token::changeKey($token['token_key'], true, false);
                    self::setClientToken($token_key);
                } else if (self::$updateLifetime)
                    Token::updateLifetime($token['token_key']);
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

    public static function logout($link = null, $isRedirect = true, $rawLink = false)
    {
        if (self::isLoggedIn()) {

            self::removeToken();
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

    private static function removeToken()
    {
        $token_key = self::getTokenKey();
        if (empty($token_key)) return;
        Token::delete($token_key);
        if (!TokenModel::fetch_by_key_without_app($token_key)) {
            switch (self::$type) {
                case self::COOKIE:
                    Cookie::destroy('pinoox_user');
                    break;
                case self::SESSION:
                    Session::remove('pinoox_user');
                    if (Session::has())
                        Session::regenerateId(true);
                    break;
            }
        }
    }

    public static function append($key, $val = null)
    {
        if (!self::isLoggedIn()) return;
        $token = self::getToken();
        $token_key = $token['token_key'];
        $token_data = $token['token_data'];
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $token_data[$k] = $v;
            }
        } else {
            $token_data[$key] = $val;
        }

        Token::setData($token_key, $token_data);
    }

    public static function set($data)
    {
        if (!self::isLoggedIn()) return;

        $token = self::getToken();
        $token_key = $token['token_key'];

        Token::setData($token_key, $data);
    }

}
    
