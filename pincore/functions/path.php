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

use Pinoox\Portal\Path;
use Pinoox\Portal\Url;
use Pinoox\Portal\View;

if (!function_exists('url')) {
    function url(string $link = ''): string
    {
        return Url::get($link);
    }
}

if (!function_exists('assets')) {
    function assets(string $link = '', bool $isPath = false): string
    {
        $path = View::path()->assets($link);
        return $isPath ? $path : furl($path);
    }
}

if (!function_exists('furl')) {
    function furl(string $path = ''): string
    {
        return Url::path($path);
    }
}

if (!function_exists('path')) {
    function path($path = '', $package = '')
    {
        return Path::get($path, $package);
    }
}