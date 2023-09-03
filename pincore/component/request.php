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

use pinoox\component\helpers\HelperArray;
use pinoox\component\helpers\HelperString;
use pinoox\component\http\Http;
use ReflectionException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Request
{
    private static $data = [];
    private static $json = [];

    /**
     * Get params in url based on MVC format like: http//site.com/p1/p2 -> [p1,p2]
     *
     * @param null $segments if it's null return all params in array or you can get specific part by a index of numeric array
     * @return array|null
     */
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

    /**
     * Read header request
     *
     * @param null $key
     * @return array|false|null
     */
    public static function headers($key = null)
    {
        $headers = apache_request_headers();
        if (empty($key)) return $headers;

        return isset($headers[$key]) ? $headers[$key] : null;
    }

    /**
     * Get all the raw data
     *
     * @param $keys
     * @param null $defaults
     * @param null $validation
     * @param bool $removeNull
     * @return array
     * @throws ReflectionException
     */
    public static function input($keys, $defaults = null, $validation = null, $removeNull = false)
    {
        return HelperArray::parseParams(self::getJson(), $keys, $defaults, $validation, $removeNull);
    }

    /**
     * Get JSON data
     *
     * @return array|mixed
     */
    private static function getJson()
    {
        if (empty(self::$json)) self::$json = HelperString::decodeJson(self::getData());
        return self::$json;
    }

    /**
     * Return all the raw data
     *
     * @return array|false|string
     */
    private static function getData()
    {
        if (empty(self::$data)) self::$data = file_get_contents('php://input');
        return self::$data;
    }

    /**
     * Get specific raw data
     *
     * @param $key
     * @param null $default
     * @param null $validation
     * @return array|string|null
     */
    public static function inputOne($key, $default = null, $validation = null)
    {
        return HelperArray::parseParam(self::getJson(), $key, $default, $validation);
    }

    /**
     * Get All post data
     *
     * @param $keys
     * @param null $defaults
     * @param null $validation
     * @param bool $removeNull
     * @return array
     */
    public static function post($keys, $defaults = null, $validation = null, $removeNull = false)
    {
        return HelperArray::parseParams($_POST, $keys, $defaults, $validation, $removeNull);
    }

    /**
     * Get specific post data
     *
     * @param $key
     * @param null $default
     * @param null $validation
     * @return array|string|null
     */
    public static function postOne($key, $default = null, $validation = null)
    {
        return HelperArray::parseParam($_POST, $key, $default, $validation);
    }

    /**
     * Get all GET data
     *
     * @param $keys
     * @param null $defaults
     * @param null $validation
     * @param bool $removeNull
     * @return array
     * @throws ReflectionException
     */
    public static function get($keys, $defaults = null, $validation = null, $removeNull = false)
    {
        return HelperArray::parseParams($_GET, $keys, $defaults, $validation, $removeNull);
    }

    /**
     * Get specific GET data
     *
     * @param $key
     * @param null $default
     * @param null $validation
     * @return array|string|null
     * @throws ReflectionException
     */
    public static function getOne($key, $default = null, $validation = null)
    {
        return HelperArray::parseParam($_GET, $key, $default, $validation);
    }

    /**
     * Get all data from REQUEST
     *
     * @param $keys
     * @param null $defaults
     * @param null $validation
     * @param bool $removeNull
     * @return array
     * @throws ReflectionException
     */
    public static function all($keys, $defaults = null, $validation = null, $removeNull = false)
    {
        return HelperArray::parseParams($_REQUEST, $keys, $defaults, $validation, $removeNull);
    }

    /**
     * Get specific data from REQUEST
     *
     * @param $key
     * @param null $default
     * @param null $validation
     * @return array|string|null
     * @throws ReflectionException
     */
    public static function one($key, $default = null, $validation = null)
    {
        return HelperArray::parseParam($_REQUEST, $key, $default, $validation);
    }

    /**
     * Check is a file upload request
     *
     * @param string $file
     * @return bool
     */
    public static function isFile($file = '')
    {
        if (!empty($file)) {
            if (isset($_FILES[$file]['name']) && !empty($_FILES[$file]['name'])) return true;
        }
        return false;
    }

    /**
     * Get file upload request
     *
     * @param null $key
     * @return null
     */
    public static function file($key = null)
    {
        if (empty($key))
            return $_FILES;
        else
            if (isset($_FILES[$key]))
                return $_FILES[$key];
        return null;
    }

    /**
     * Check is POST request
     *
     * @param string $param
     * @return bool
     */
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

    /**
     * Check is GET request
     *
     * @param string $param
     * @return bool
     */
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

    /**
     * Check is Ajax request
     *
     * @return bool
     */
    public static function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'))
            return true;
        return false;

    }


    /**
     * Send post request
     *
     * @param $url string
     * @param $params array
     * @param $options array
     * @return ResponseInterface|null
     */
    public static function sendPost(string $url, array $params = [], array $options = []): ?ResponseInterface
    {
        $options['body'] = $params;
        return Http::post( $url, $options);
    }

    /**
     * Send get request
     *
     * @param $url string
     * @param $options array
     * @return array|string|bool|mixed
     */
    public static function sendGet($url, $options = [])
    {
        $params = isset($options['params']) ? $options['params'] : [];
        return HttpRequest::init($url, HttpRequest::GET)->params($params)->options($options)->send();
    }
}