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

use pinoox\model\SessionModel;

class Session
{
    private static $salt = 'Fg$vv4513DKOzQEvC$&#DK';
    private static $store_in_file = false;
    private static $lifeTime = 0;
    private static $gc_probability = null;
    private static $gc_divisor = null;
    private static $non_blocking = false;
    private static $security_token = false;
    private static $is_ip = false;
    private static $encryption = false;
    private static $save_empty = false;
    private static $app = null;
    private static $is_start = false;

    public function __construct($store_in_file = false)
    {
        self::$store_in_file = $store_in_file;
    }

    public static function _gc($lifetime)
    {
        SessionModel::remove_all_expired($lifetime);

        return true;
    }

    public static function has()
    {
        $parts = self::getKeyFromArgs(func_num_args(), func_get_args());
        return HelperArray::isExistsValueByNestedKey($_SESSION, $parts);
    }

    public static function set($key, $value = null)
    {
        $_SESSION[$key] = $value;
        if (self::$non_blocking)
            session_write_close();
    }

    public static function get()
    {
        if (func_num_args() == 0)
            return self::getAll();

        $parts = self::getKeyFromArgs(func_num_args(), func_get_args());
        return HelperArray::getValueByNestedKey($_SESSION, $parts);
    }

    private static function getAll()
    {
        if (isset($_SESSION))
            return $_SESSION;
        else
            return [];
    }

    private static function getKeyFromArgs($num, $args)
    {
        $keys = '';
        if ($num > 1) {
            foreach ($args as $arg)
                $keys .= $arg . '.';
            $keys = substr($keys, 0, strlen($keys) - 1);
        } else {
            if (isset($args[0]))
                $keys = $args[0];
        }
        $parts = explode('.', $keys);
        return $parts;
    }

    public static function app($package_name)
    {
        if(!empty(self::$app)) {
            session_write_close();
            session_start();
        }
        self::$app = $package_name;
        if(function_exists('session_reset'))
            session_reset();

    }

    public static function getApp()
    {
        return (!empty(self::$app)) ? self::$app : Router::getApp();
    }

    /**
     * @param $lifeTime : in seconds
     * @param $type : sec | min | hour | day
     */
    public static function lifeTime($lifeTime, $type = 'sec')
    {
        if ($type == 'min') $lifeTime = $lifeTime * 60;
        if ($type == 'hour') $lifeTime = $lifeTime * 60 * 60;
        if ($type == 'day') $lifeTime = $lifeTime * 60 * 60 * 24;
        self::$lifeTime = $lifeTime;
    }

    public static function saveEmpty($status = false)
    {
        self::$save_empty = $status;
    }

    public static function gcProbability($gc_probability)
    {
        self::$gc_probability = $gc_probability;
    }

    public static function gcDivisor($gc_divisor)
    {
        self::$gc_divisor = $gc_divisor;
    }

    public static function nonBlocking($status = true)
    {
        self::$non_blocking = $status;
    }

    /**
     * make a token with ip and user agent for preventing Session hijacking and fixation attack
     * @param $security_token -> true or false
     */
    public static function securityToken($security_token, $is_ip = false)
    {
        self::$security_token = $security_token;
        self::$is_ip = $is_ip;
    }

    /**store session data encrypted
     * @param $status : true or false
     */
    public static function encryption($status)
    {
        self::$encryption = $status;
    }

    /**
     * @param $status true OR false
     * true → regenerate session id
     * set true after logout or login for changing session id and preventing fixation attack
     */
    public static function regenerateId($status)
    {
        session_regenerate_id($status);
    }

    public static function remove()
    {
        $parts = self::getKeyFromArgs(func_num_args(), func_get_args());
        HelperArray::removeValueByNestedKey($_SESSION, $parts);
    }

    public function start()
    {
        // this makes it harder for an attacker to hijack the session ID
        ini_set('session.cookie_httponly', 1);
        // make sure that PHP only uses cookies for sessions and disallow session ID passing as a GET parameter
        ini_set('session.use_only_cookies', 1);
        if (!empty(self::$lifeTime)) {
            session_set_cookie_params(self::$lifeTime);
            // make sure session cookies never expire
            ini_set('session.cookie_lifetime', self::$lifeTime);
            ini_set('session.gc_maxlifetime', self::$lifeTime);
        }
        if (!is_null(self::$gc_probability))
            ini_set('session.gc_probability', self::$gc_probability);
        if (!is_null(self::$gc_divisor))
            ini_set('session.gc_divisor', self::$gc_divisor);

        if (!self::$store_in_file) {
            // Set handler to override SESSION
            session_set_save_handler(
                array($this, "_open"),
                array($this, "_close"),
                array($this, "_read"),
                array($this, "_write"),
                array($this, "_destroy"),
                array($this, "_gc")
            );
        }
        // start the session
        session_start();
        self::$is_start = true;
    }

    public static function isStartOnDatabase()
    {
        return (!self::$store_in_file && self::$is_start);
    }

    public function _open($save_path, $session_name)
    {
        if (!self::$store_in_file)
            return true;
        return false;
    }

    public function _close()
    {
        if (!self::$store_in_file)
            return true;
        return false;
    }

    public function _read($id)
    {
        if (!self::$store_in_file) {
            $session = SessionModel::fetch_by_id($id, self::$lifeTime, $this->getSecurityToken());
            if (empty($session) || empty($session['session_data'])) {
                return '';
            } else {
                return self::$encryption === true ? $this->decrypt($session['session_data']) : $session['session_data'];
            }
        } else {
            return '';
        }
    }

    private function getSecurityToken()
    {
        if (self::$security_token) {
            $ip = HelperHeader::getIP();
            $useragent = HelperHeader::getUserAgent();
            return $sec_token = (self::$is_ip) ? md5(self::$salt . $ip . $useragent) : md5(self::$salt . $useragent);
        }
        return null;
    }

    /**
     * decrypt AES 256
     *
     * @param data $edata
     * @param string $password
     * @return decrypted data
     */
    private static function decrypt($edata, $password = null)
    {
        if (empty($password)) {
            $ip = HelperHeader::getIP();
            $useragent = HelperHeader::getUserAgent();
            $password = (self::$is_ip) ? md5(self::$salt . $ip . $useragent) : md5(self::$salt . $useragent);
        }
        $data = base64_decode($edata);
        $salt = substr($data, 0, 16);
        $ct = substr($data, 16);

        $rounds = 3; // depends on key length
        $data00 = $password . $salt;
        $hash = array();
        $hash[0] = hash('sha256', $data00, true);
        $result = $hash[0];
        for ($i = 1; $i < $rounds; $i++) {
            $hash[$i] = hash('sha256', $hash[$i - 1] . $data00, true);
            $result .= $hash[$i];
        }
        $key = substr($result, 0, 32);
        $iv = substr($result, 32, 16);

        return openssl_decrypt($ct, 'AES-256-CBC', $key, true, $iv);
    }

    public function _write($session_id, $session_data)
    {
        if (!self::$store_in_file) {
            if (empty($session_data)) {
                SessionModel::delete($session_id);
                return true;
            }
            if (self::$encryption === true) {
                $session_data = $this->encrypt($session_data);
            }
            if (SessionModel::is_exists($session_id)) {
                return SessionModel::update($session_id, $session_data,self::$lifeTime);
            } else {
                SessionModel::delete($session_id);
                $id = SessionModel::insert($session_id, $session_data, self::$lifeTime, $this->getSecurityToken());
                return $id > 0 ? true : false;
            }
        }
        return false;
    }

    /**
     * crypt AES 256
     *
     * @param data $data
     * @param string $password
     * @return base64 encrypted data
     */
    private function encrypt($data, $password = null)
    {
        if (empty($password)) {
            $ip = HelperHeader::getIP();
            $useragent = HelperHeader::getUserAgent();
            $password = (self::$is_ip) ? md5(self::$salt . $ip . $useragent) : md5(self::$salt . $useragent);
        }
        // Set a random salt
        $salt = openssl_random_pseudo_bytes(16);

        $salted = '';
        $dx = '';
        // Salt the key(32) and iv(16) = 48
        while (strlen($salted) < 48) {
            $dx = hash('sha256', $dx . $password . $salt, true);
            $salted .= $dx;
        }

        $key = substr($salted, 0, 32);
        $iv = substr($salted, 32, 16);

        $encrypted_data = openssl_encrypt($data, 'AES-256-CBC', $key, true, $iv);
        return base64_encode($salt . $encrypted_data);
    }

    public function _destroy($session_id)
    {
        // delete the current session id from the database
        $status = SessionModel::delete($session_id);
        setcookie(session_name(), "", time() - 3600);
        return $status ? true : false;
    }

    public static function getSessionId()
    {
        return session_id();
    }

    public static function clear()
    {
        session_unset();
        session_destroy();
    }

    public static function stop()
    {
        session_write_close();
        session_unset();
        session_destroy();
    }
}
    
