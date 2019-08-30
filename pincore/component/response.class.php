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

class Response
{
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
    }

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


    public static function jsonMessage($message, $status, $result = null, $exit = true)
    {
        self::json(array("status" => $status, "result" => $result, "message" => $message), null, $exit);
    }

    public static function jsonError($message, $statusCode = 400)
    {
        http_response_code($statusCode);
        self::json(['statusCode' => $statusCode, 'message' => $message]);
    }
}