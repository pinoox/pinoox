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

class Request
{
    private static $data = [];
    private static $json = [];

    private static function getData()
    {
        if (empty(self::$data)) self::$data = file_get_contents('php://input');
        return self::$data;
    }

    private static function getJson()
    {
        if (empty(self::$json)) self::$json = HelperString::decodeJson(self::getData());
        return self::$json;
    }

    public static function params($segments = null)
    {
        $args = Router::params();
        if (empty($args)) return null;
        if (is_null($segments)) return $args;

        if (is_array($segments)) {
            $params = array();
            foreach ($segments as $s) {
                $params[] = isset($args[$s]) ? $args[$s] : null;
            }
            return $params;
        }
        return $data = isset($args[$segments]) ? $args[$segments] : null;
    }

    public static function headers($key = null)
    {
        $headers = apache_request_headers();
        if (empty($key)) return $headers;

        return isset($headers[$key]) ? $headers[$key] : null;
    }

    public static function input($keys, $defaults = null, $validation = null, $removeNull = false)
    {
        return HelperArray::parseParams(self::getJson(), $keys, $defaults, $validation, $removeNull);
    }

    public static function inputOne($key, $default = null, $validation = null)
    {
        return HelperArray::parseParam(self::getJson(), $key, $default, $validation);
    }

    public static function post($keys, $defaults = null, $validation = null, $removeNull = false)
    {
        return HelperArray::parseParams($_POST, $keys, $defaults, $validation, $removeNull);
    }

    public static function postOne($key, $default = null, $validation = null)
    {
        return HelperArray::parseParam($_POST, $key, $default, $validation);
    }

    public static function get($keys, $defaults = null, $validation = null, $removeNull = false)
    {
        return HelperArray::parseParams($_GET, $keys, $defaults, $validation, $removeNull);
    }

    public static function getOne($key, $default = null, $validation = null)
    {
        return HelperArray::parseParam($_GET, $key, $default, $validation);
    }

    public static function all($keys, $defaults = null, $validation = null, $removeNull = false)
    {
        return HelperArray::parseParams($_REQUEST, $keys, $defaults, $validation, $removeNull);
    }

    public static function one($key, $default = null, $validation = null)
    {
        return HelperArray::parseParam($_REQUEST, $key, $default, $validation);
    }

    public static function isFile($file = '')
    {
        if (!empty($file)) {
            if (isset($_FILES[$file]['name']) && !empty($_FILES[$file]['name'])) return true;
        }
        return false;
    }

    public static function file($key = null)
    {
        if (empty($key))
            return $_FILES;
        else
            if (isset($_FILES[$key]))
                return $_FILES[$key];
        return null;
    }

    public static function isPost($param = '')
    {
        if (!empty($param)) {
            if (isset($_POST[$param])) return true;
            else return false;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST')
            return true;
        return false;
    }

    public static function isGet($param = '')
    {
        if (!empty($param)) {
            if (isset($_GET[$param])) return true;
            else return false;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET')
            return true;
        return false;
    }

    public static function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'))
            return true;
        return false;

    }
}