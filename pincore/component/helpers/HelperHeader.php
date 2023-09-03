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

namespace pinoox\component\helpers;

class HelperHeader
{
    private static $isContentType = false;

    // redirect url
    public static function redirect($url)
    {
        self::addHeader('Location', $url);
    }

    // generate status code for http

    public static function addHeader($key, $value = null, $replace = true, $http_response_code = null)
    {
        if (!empty($value))
            header($key . ": " . $value, $replace, $http_response_code);
        else
            header($key, $replace, $http_response_code);
    }

    // set mimeType

    public static function generateStatusCodeHTTP($input, $version = '1.1')
    {
        self::addHeader("HTTP/" . $version . " " . $input);
    }

    // set Content Length

    public static function contentType($mimeType, $charset = null, $boundary = null)
    {
        if(self::$isContentType)
            return;

        self::$isContentType = true;
        if (empty($charset) && empty($boundary))
            self::addHeader('Content-Type', $mimeType);
        else if (!empty($charset))
            self::addHeader('Content-Type', $mimeType . '; charset=' . $charset);
        else
            self::addHeader('Content-Type', $mimeType . '; boundary=' . $boundary);

    }

    // called file by new name and type download file

    public static function contentLength($size)
    {
        self::addHeader('Content-Length', $size);
    }

    /*
     * Content Transfer Encoding : BASE64 - QUOTED-PRINTABLE - 8BIT - 7BIT - BINARY - x-token
     */

    public static function contentDisposition($name, $isAttachment = false)
    {
        if (!empty($name)) {
            $name = '; filename=' . $name;
        }

        if ($isAttachment)
            self::addHeader('Content-Disposition', 'attachment' . $name);
        else
            self::addHeader('Content-Disposition', 'inline' . $name);
    }

    /*
     * Content Transfer Encoding : bytes - none
     */

    public static function contentTransferEncoding($typeEncoding)
    {
        self::addHeader('Content-Transfer-Encoding', $typeEncoding);
    }

    /*
     * The Cache-Control general-header field is used to specify directives
     * for caching mechanisms in both, requests and responses
     *
     * ---------- Cache request directives --------------------
     * + Cache-Control: max-age=<seconds>
     * + Cache-Control: max-stale[=<seconds>]
     * + Cache-Control: min-fresh=<seconds>
     * + Cache-Control: no-cache
     * + Cache-Control: no-store
     * + Cache-Control: no-transform
     * + Cache-Control: only-if-cached
     *
     * ---------- Cache response directives -------------------
     * + Cache-Control: must-revalidate
     * + Cache-Control: no-cache
     * + Cache-Control: no-store
     * + Cache-Control: no-transform
     * + Cache-Control: public
     * + Cache-Control: private
     * + Cache-Control: proxy-revalidate
     * + Cache-Control: max-age=<seconds>
     * + Cache-Control: s-maxage=<seconds>
     *
     * ---------- Extension Cache-Control directives ----------
     * + Cache-Control: immutable
     * + Cache-Control: stale-while-revalidate=<seconds>
     * + Cache-Control: stale-if-error=<seconds>
     *
     * #### Example #####
     * HelperHeader::cacheControl('no-cache, no-store, must-revalidate');
     *
     */

    public static function acceptRanges($unit = 'none')
    {
        self::addHeader('Accept-Ranges', $unit);
    }

    /*
     * Pragma : like Cache-Control But with HTTP/1.0 caches
     *
     * The Pragma HTTP/1.0 general header is an implementation-specific header
     * that may have various effects along the request-response chain.
     *  It is used for backwards compatibility
     * with HTTP/1.0 caches where the Cache-Control HTTP/1.1 header is not yet present.
     *
     * Note >>
     * Pragma is not specified for HTTP responses and is therefore not a reliable replacement
     *  for the general HTTP/1.1 Cache-Control header,
     *  although it does behave the same as Cache-Control: no-cache,
     *  if the Cache-Control header field is omitted in a request.
     *  Use Pragma only for backwards compatibility with HTTP/1.0 clients.
     */

    public static function cacheControl($string)
    {
        self::addHeader('Cache-control', $string);
    }

    /*
     * The Expires header contains the date/time after which the response is considered stale.
     * Invalid dates, like the value 0, represent a date in the past
     * and mean that the resource is already expired.
     *
     * #### Example #####
     * HelperHeader::expires('Wed, 21 Oct 2015 07:28:00 GMT');
     */

    public static function pragma($string)
    {
        self::addHeader('Pragma', $string);
    }

    /*
     * The Content-Range response HTTP header
     * indicates where in a full body message a partial message belongs.
     *
     * $unit : The unit in which ranges are specified. This is usually bytes.
     * $size : The total size of the document (An integer)
     * $range <range-start>-<range-end>  : '200-1000'
     * $size or $range  '*' if unknown
     *
     * #### Examples #####
     * + HelperHeader::expires('bytes','200-1000',67589);
     * + HelperHeader::expires('bytes',*,67589);
     * + HelperHeader::expires('bytes','200-1000',*);
     */

    public static function expires($dateTimeString)
    {
        self::addHeader('Expires', $dateTimeString);
    }

    // add header

    public static function contentRange($unit, $range = '*', $size = '*')
    {
        self::addHeader('Content-Range', $unit . ' ' . $range . '/' . $size);
    }

    public static function is_bot($bots = array('googlebot', 'bing', 'msnbot'))
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            return preg_match('/(' . implode('|', $bots) . ')/i', ($_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : @getenv('HTTP_USER_AGENT'))) ? true : false;
        }
        return false;
    }

    public static function getIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public static function getUserAgent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }


    public static function isLocalhost()
    {
        $whitelist = array('127.0.0.1', "::1");

        if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            return true;
        }
        return false;
    }
}