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

    /**
     * The default salt for security reasons.
     *
     * @var string
     */
    private static $salt = 'Fg$vv4513DKOzQEvC$&#DK';

    /**
     * The Time of Session that is alive
     *
     * @var int
     */
    private static $lifeTime = 0;

    /**
     * Probability session data garbage collection
     *
     * @var int|null
     */
    private static $gc_probability = null;

    /**
     * It's depend on gc_probability when you set gc_probability=1 and gc_divisor=100 means there is a 1% chance that the GC process starts on each request.
     *
     * @var int|null
     */
    private static $gc_divisor = null;

    /**
     * When is true it is sensitive to user agents and IP changes
     *
     * @var bool
     */
    private static $security_token = false;

    /**
     * When is true it is sensitive to IP changes
     *
     * @var bool
     */
    private static $is_ip = false;

    /**
     * Encrypt generated session and it's data
     *
     * @var bool
     */
    private static $encryption = false;

    /**
     * Store session in file or database.
     *
     * default state is false means store in file, For store in database use true state.
     *
     * @var bool
     */
    private static $store_in_file = false;

    /**
     * Locking Session on concurrent requests
     *
     * @var bool
     */
    private static $non_blocking = false;

    /**
     * The app name
     *
     * @var null
     */
    private static $app = null;

    /**
     * Check is started on database
     *
     * @var bool
     */
    private static $is_start = false;

    /**
     * Session constructor
     *
     * @param bool $store_in_file
     */
    public function __construct($store_in_file = false)
    {
        self::$store_in_file = $store_in_file;
    }

    /**
     * Call Garbage Collector
     *
     * @param $lifetime
     * @return bool
     */
    public static function _gc($lifetime)
    {
        SessionModel::remove_all_expired($lifetime);

        return true;
    }

    /**
     * Check specific key in Session
     *
     * @return bool
     */
    public static function has()
    {
        $parts = self::getKeyFromArgs(func_num_args(), func_get_args());
        return HelperArray::existsNestedKey($_SESSION, $parts);
    }

    /**
     * Set data in Session
     *
     * @param $key
     * @param null $value
     * @return void
     */
    public static function set($key, $value = null)
    {
        $_SESSION[$key] = $value;
        if (self::$non_blocking)
            session_write_close();
    }

    /**
     * Get data stored in Session by key
     *
     * @return array|null
     */
    public static function get()
    {
        if (func_num_args() == 0)
            return self::getAll();

        $parts = self::getKeyFromArgs(func_num_args(), func_get_args());
        return HelperArray::getNestedKey($_SESSION, $parts);
    }

    /**
     * Get all data stored in Session
     *
     * @return array
     */
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


    /**
     * Get Session of specific app
     *
     * @param $package_name
     */
    public static function app($package_name)
    {
        if (!empty(self::$app)) {
            session_write_close();
            session_start();
        }
        self::$app = $package_name;
        if (function_exists('session_reset'))
            session_reset();

    }

    /**
     * Get App name
     *
     * @return null|string
     */
    public static function getApp()
    {
        return (!empty(self::$app)) ? self::$app : Router::getApp();
    }

    /**
     * Lifetime Session
     *
     * @param $lifeTime
     * @param string $unitTime units are sec,min,hour,day
     */
    public static function lifeTime($lifeTime, $unitTime = 'sec')
    {
        if ($unitTime == 'min') $lifeTime = $lifeTime * 60;
        if ($unitTime == 'hour') $lifeTime = $lifeTime * 60 * 60;
        if ($unitTime == 'day') $lifeTime = $lifeTime * 60 * 60 * 24;
        self::$lifeTime = $lifeTime;
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
     * Make a token with ip and user agent for preventing Session hijacking and fixation attack
     *
     * @param $security_token -> true or false
     * @param bool $is_ip
     * @return void
     */
    public static function securityToken($security_token, $is_ip = false)
    {
        self::$security_token = $security_token;
        self::$is_ip = $is_ip;
    }

    /**
     * Encrypt when save data
     * @param $status
     */
    public static function encryption($status)
    {
        self::$encryption = $status;
    }

    /**
     * Prevent fixation attack
     * If $status is true after each login or logout call regenerate_id() for changing session_id
     *
     * @param $regenerate_id
     */
    public static function regenerateId($regenerate_id)
    {
        session_regenerate_id($regenerate_id);
    }

    /**
     * Remove Session
     *
     * @param ... key of specific index
     */
    public static function remove()
    {
        $parts = self::getKeyFromArgs(func_num_args(), func_get_args());
        HelperArray::removeNestedKey($_SESSION, $parts);
    }

    /**
     * Start Session
     */
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

    /**
     * Checking Session use database or file
     *
     * @return bool
     */
    public static function isStartOnDatabase()
    {
        return (!self::$store_in_file && self::$is_start);
    }

    /**
     * Determine Session Where to store with specific name
     *
     * @param $save_path
     * @param $session_name
     * @return bool
     */
    public function _open($save_path, $session_name)
    {
        if (!self::$store_in_file)
            return true;
        return false;
    }

    /**
     * Stop Session
     *
     * @return bool
     */
    public function _close()
    {
        if (!self::$store_in_file)
            return true;
        return false;
    }

    /**
     * Read data from specific session id
     *
     *
     * @param $id
     * @return decrypted|string
     */
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

    /**
     * Get security token
     *
     * @return string|null
     */
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
     * Decrypt AES 256
     *
     * @param mixed $edata
     * @param string $password
     * @return mixed data
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

    /**
     * Write data for specific session id
     *
     * @param $session_id
     * @param $session_data
     * @return bool
     */
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
                return SessionModel::update($session_id, $session_data, self::$lifeTime);
            } else {
                SessionModel::delete($session_id);
                $id = SessionModel::insert($session_id, $session_data, self::$lifeTime, $this->getSecurityToken());
                return $id > 0 ? true : false;
            }
        }
        return false;
    }

    /**
     * Crypt AES 256
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

    /**
     * Destroy Session
     *
     * @param $session_id
     * @return bool
     */
    public function _destroy($session_id)
    {
        // delete the current session id from the database
        $status = SessionModel::delete($session_id);
        setcookie(session_name(), "", time() - 3600);
        return $status ? true : false;
    }

    /**
     * Get Session id
     *
     * @return string
     */
    public static function getSessionId()
    {
        return session_id();
    }

    /**
     * Destroy and close all Sessions
     */
    public static function clear()
    {
        session_unset();
        session_destroy();
    }

    /**
     * Destroy and close all Sessions
     */
    public static function stop()
    {
        session_write_close();
        session_unset();
        session_destroy();
    }
}
    
