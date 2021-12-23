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

use pinoox\component\app\AppProvider;

class Url
{
    private static $theme = null;
    private static $pathTheme = null;


    public static function queryString($decode = true)
    {
        $q = $_SERVER['QUERY_STRING'];
        if ($decode) $q = urldecode($q);
        return $q;
    }

    public static function parts($index = null)
    {
        $parts = substr(self::current(), strripos(self::app() . '/', '/'));
        if (!is_null($index)) {

            $partsArr = explode('/', $parts);
            if ($index == 'first') return reset($partsArr);
            if ($index == 'last') return end($partsArr);

            return isset($partsArr[$index]) ? $partsArr[$index] : null;
        }
        return $parts;
    }

    public static function current()
    {
        return self::fullDomain() . self::request();
    }

    public static function fullDomain()
    {
        return self::protocol() . '://' . self::domain();
    }

    public static function protocol()
    {
        return self::isHttps()? 'https' : 'http';
    }

    public static function domain()
    {
        return isset($_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : null;
    }

    public static function request()
    {
        return isset($_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null;
    }

    public static function app()
    {
        $appUrl = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
        if (!Router::isAppDefault()) $appUrl .= self::appKey() . '/';

        return self::fullDomain() . $appUrl;
    }

    public static function appKey()
    {
        return Router::getAppUrl();
    }

    public static function isHttps()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    public static function file($path, $app = null)
    {
        $dir = Dir::path($path, $app);
        return self::link('~' . $dir);
    }

    public static function link($link = null)
    {
        $result = self::site();

        if (HelperString::firstHas($link, '^')) {
            $link = HelperString::firstDelete($link, '^');
            $result = HelperString::firstDelete($result, self::fullDomain());
        }

        $isBase = false;
        if (!HelperString::firstHas($link, '~')) {
            $result .= !empty(self::appKey()) ? self::appKey() . '/' : '';
        } else {
            $link = HelperString::firstDelete($link, '~');
            $isBase = true;
        }
        if (!is_null($link)) {
            if (!$isBase) {
                $link = HelperString::firstDelete($link, Dir::path());
                $link = HelperString::firstDelete($link, self::app());
            } else {
                $link = HelperString::firstDelete($link, PINOOX_PATH);
                $link = HelperString::firstDelete($link, self::site());
            }
            $link = str_replace(['\\', '>'], '/', $link);
            $link = HelperString::firstDelete($link, '/');
            $result = $result . $link;
        }
        return $result;
    }

    public static function site()
    {
        $siteUrl = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
        return self::fullDomain() . $siteUrl;
    }

    public static function check($link, $default = null)
    {
        if (!empty($link) && self::existsFile($link)) {
            return $link;
        } else {
            return $default;
        }
    }

    public static function existsFile($link)
    {
        if (empty($link)) return false;
        $path = Dir::path('~' . $link);

        if (is_file($path)) {
            return true;
        }
        return false;
    }

    public static function setTheme($theme, $path = null)
    {
        self::$theme = $theme;
        self::$pathTheme = $path;
    }

    public static function theme($url = null, $theme = null, $path = null)
    {
        if (empty($theme))
            $theme = (empty(self::$theme)) ? AppProvider::get('theme') : self::$theme;
        if (empty($path))
            $path = (empty(self::$pathTheme)) ? Dir::path(AppProvider::get('path-theme')) : self::$pathTheme;

        $dir = Dir::theme($url, $theme, $path);
        return self::link('~' . $dir);
    }

    public static function upload($row, $defaultLink = null, $isCheck = true)
    {
        $path = Dir::upload($row, null, $isCheck);
        return (!empty($path)) ? self::link('~' . $path) : $defaultLink;
    }

    public static function thumb($img, $thumbSize = 128, $defaultImage = null, $path = PINOOX_PATH_THUMB, $isCreateThumb = false, $isCheck = true)
    {
        if (!is_array($img) && !is_numeric($img))
            $img = HelperString::firstDelete($img, self::link('~'));
        $path = Dir::thumb($img, $thumbSize, null, $path, $isCreateThumb, $isCheck);
        return (!empty($path)) ? self::link('~' . $path) : $defaultImage;
    }

}
