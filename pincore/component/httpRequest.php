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


use mysql_xdevapi\Exception;
use pinoox\component\helpers\HelperHeader;
use pinoox\component\helpers\HelperString;

class HttpRequest
{

    const POST = 'POST';
    const GET = 'GET';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const PATCH = 'PATCH';


    const json = 'json';
    const form = 'form';

    /**
     * An instance of HttpRequest Class (self)
     *
     * @var HttpRequest
     */
    private static $http;

    /**
     * Request url
     *
     * @var string
     */
    private $url;

    /**
     * Request method
     *
     * @var string
     */
    private $method;

    /**
     * Request params
     *
     * @var array
     */
    private $params = [];

    /**
     * Other settings in request
     *
     * @var array
     */
    private $options = [];

    /**
     * HttpRequest constructor
     *
     * @param string $url
     * @param string $method
     */
    public function __construct($url, $method = self::GET)
    {
        $this->url = $url;
        $this->method = $method;
        $this->type();
    }

    /**
     * Set type send params
     *
     * @param string $type
     * @return HttpRequest
     */
    public function type($type = self::json)
    {
        $this->options['type'] = $type;
        $this->buildType();
        return self::$http;
    }

    /**
     * Build header by type params
     */
    private function buildType()
    {
        $type = isset($this->options['type']) ? $this->options['type'] : self::json;
        if ($type === self::json)
            $this->setHeader('Content-Type', 'application/json');
        else if ($type === self::form)
            $this->setHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    /**
     * Set header for request
     *
     * @param string $key
     * @param string $value
     * @return HttpRequest
     */
    public function setHeader($key, $value)
    {
        if (!isset($this->options['headers']))
            $this->options['headers'] = [];
        $this->options['headers'][$key] = $value;

        return self::$http;
    }

    /**
     * Get instance of HttpRequest
     * @param string $url
     * @param string $method
     * @return HttpRequest
     */
    public static function init($url, $method = self::GET, $isCache = true)
    {
        if (!$isCache || empty(self::$http))
            self::$http = new HttpRequest($url, $method);

        return self::$http;
    }

    public static function isOnline()
    {
        if (checkdnsrr('pinoox.com') || checkdnsrr('w3.org') || checkdnsrr('google.com'))
            return true;

        return false;
    }

    /**
     * Set params for request
     *
     * @param array $data
     * @return HttpRequest
     */
    public function params($data)
    {
        $this->params = $data;

        return self::$http;
    }

    /**
     * set param for request
     *
     * @param string $key
     * @param string $value
     * @return HttpRequest
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;

        return self::$http;
    }

    /**
     * @param $data
     * @return HttpRequest
     */
    public function options($data)
    {
        $this->options = $data;
        if (empty($this->getHeaders('Content-Type'))) {
            $this->buildType();
        }

        return self::$http;
    }

    /**
     * Get Headers for request
     *
     * @param string|null $key
     * @return array|mixed|null
     */
    private function getHeaders($key = null)
    {
        $headers = isset($this->options['headers']) ? $this->options['headers'] : [];
        if (!is_null($key)) {
            return (isset($headers[$key])) ? $headers[$key] : null;
        } else {
            $result = [];
            foreach ($headers as $key => $value) {
                $result[] = $key . ': ' . $value;
            }

            return $result;
        }
    }

    /**
     * Set option (other setting)
     *
     * @param string $key
     * @param string $value
     * @return HttpRequest
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;

        return self::$http;
    }

    /**
     * set Headers
     *
     * @param $data
     * @return HttpRequest
     */
    public function headers($data)
    {
        $this->options['headers'] = $data;

        return self::$http;
    }

    /**
     * Set timeout for request
     *
     * @param int $ms
     * @return HttpRequest
     */
    public function timeout($ms)
    {
        $this->options['timeout'] = $ms;

        return self::$http;
    }

    /**
     * Set status background
     *
     * @param bool $status
     * @return HttpRequest
     */
    public function background($status)
    {
        $this->options['background'] = $status;

        return self::$http;
    }

    /**
     * Send Request
     *
     * @param string|null $raw
     * @return bool|false|mixed|string
     */
    public function send($raw = null)
    {
        $result = false;

        $user_agent = $this->getHeaders('User-Agent');

        if (empty($user_agent))
            $this->setHeader('User-Agent', HelperHeader::getUserAgent() . ' pinoox');

        if ($this->checkEnableLib('curl')) {
            $result = $this->sendCurl();
        } else if ($this->checkEnableLib('file_content')) {
            $result = $this->sendContents();
        }

        $result = ($result && $raw === self::json) ? HelperString::decodeJson($result) : $result;

        return $result;
    }

    /**
     * Check require source
     *
     * @param string $type
     * @return bool
     */
    private function checkEnableLib($type)
    {
        switch ($type) {
            case 'curl':
                return function_exists('curl_init');
            case 'file_content':
                return function_exists('file_get_contents');
        }

        return false;
    }

    /**
     *  send request by curl library
     *
     * @return bool|string|array|null|mixed
     */
    private function sendCurl()
    {
        try {
            // init
            $curl = curl_init();
            $url = $this->url;

            if ($curl === false) {
                throw new Exception('Curl failed to initialize');
            }

            // method
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);

            switch ($this->method) {
                case self::POST:
                    curl_setopt($curl, CURLOPT_POST, 1);
                    break;
                case self::GET:
                    if (!empty($this->params))
                        $url = sprintf("%s?%s", $this->url, http_build_query($this->params));
                    break;
            }

            if (in_array($this->method, [self::PATCH, self::PUT, self::POST])) {
                if (!empty($this->params)) {
                    $params = $this->getParamsByType();
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                }
            }

            // options
            $headers = $this->getHeaders();
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, Url::isHttps());
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 10);

            $hasBackground = $this->getBackground();
            if ($hasBackground) {
                curl_setopt($curl, CURLOPT_TIMEOUT, 1);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
            }

            $timeout = $this->getTimeout();
            if (!empty($timeout))
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, $timeout);

            $user_agent = $this->getHeaders('User-Agent');
            if (!empty($user_agent))
                curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);

            // execute
            $result = curl_exec($curl);
            curl_close($curl);
        } catch (Exception $error) {
            trigger_error(sprintf(
                'Curl failed with error #%d: %s',
                $error->getCode(), $error->getMessage()),
                E_USER_ERROR);
        }
        return $result ? $result : false;
    }

    /**
     * Get params by type
     *
     * @return array|false|string
     */
    private function getParamsByType()
    {
        $type = isset($this->options['type']) ? $this->options['type'] : self::json;
        if ($type === self::json)
            return HelperString::encodeJson($this->params);
        else if ($type === self::form)
            return http_build_query($this->params);
        else
            return $this->params;
    }

    /**
     * Get status background
     *
     * @return bool
     */
    private function getBackground()
    {
        return isset($this->options['background']) ? $this->options['background'] : false;
    }

    /**
     * Get ms timeout
     *
     * @return int|null
     */
    private function getTimeout()
    {
        return isset($this->options['timeout']) ? $this->options['timeout'] : null;
    }

    /**
     *  send request by file_get_contents library
     *
     * @return bool|string|array|null|mixed
     */
    private function sendContents()
    {
        // init
        $url = $this->url;

        // method and options
        $headers = $this->getHeaders();
        $headers = implode("\r\n", $headers);
        $options = array(
            'http' => array(
                'header' => $headers,
                'method' => $this->method,
            )
        );
        $timeout = $this->getTimeout();
        if (!empty($timeout))
            $options['http']['timeout'] = $timeout / 1000;

        $user_agent = $this->getHeaders('User-Agent');
        if (!empty($user_agent))
            $options['http']['user_agent'] = $user_agent;

        // params
        if ($this->method === self::POST || $this->method === self::PUT || $this->method === self::PATCH)
            $options['http']['content'] = $this->getParamsByType();
        else
            $url = sprintf("%s?%s", $this->url, http_build_query($this->params));

        // execute
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }
}