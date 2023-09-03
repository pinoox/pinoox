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

use pinoox\component\helpers\HelperHeader;

class Response
{
    /**
     * Redirect to a location
     *
     * @param $url
     * @param bool $header
     * @param bool $exit force to exit
     * @param int $sec
     * @param bool $return for returning args
     * @return string
     */
    public static function redirect($url, $header = true, $exit = true, $sec = 0, $return = false)
    {
        if (!headers_sent() && $header && !$return) {
            header("Location: $url");
        } else {
            $gre = '<script type="text/javascript"> setTimeout("window.location.href = \'' . str_replace(array('&amp;'), array('&'), $url) . '\'", ' . $sec * 1000 . '); </script>';
            $gre .= '<noscript><meta http-equiv="refresh" content="' . $sec . ';url=' . $url . '" /></noscript>';
            if ($return)
                return $gre;
            echo $gre;
        }
        if ($exit)
            exit;

        return null;
    }

    /**
     * Response in json formant
     *
     * @param $result
     * @param null $status
     * @param bool $exit
     */
    public static function json($result, $status = null, $exit = true)
    {
        HelperHeader::contentType('application/json', 'UTF-8');

        if (is_null($status)) {
            echo json_encode($result);
        } else {
            echo json_encode(array("status" => $status, "result" => $result));
        }
        if ($exit) exit;
    }

    /**
     * Response in json format with extra message data
     *
     * @param $message
     * @param $status
     * @param null $result
     * @param bool $exit
     */
    public static function jsonMessage($message, $status, $result = null, $exit = true)
    {
        self::json(array("status" => $status, "result" => $result, "message" => $message), null, $exit);
    }

    /**
     * Response in json format for errors
     *
     * @param $message
     * @param int $statusCode
     */
    public static function jsonError($message, $statusCode = 400)
    {
        http_response_code($statusCode);
        self::json(['statusCode' => $statusCode, 'message' => $message]);
    }
}