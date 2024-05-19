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

use Pinoox\Component\Helpers\Str;
use Pinoox\Portal\Lang;

if (!function_exists('lang')) {
    function lang($key, array $replace = [], $locale = NULL, $fallback = true)
    {
        $result = Lang::get($key, $replace, $locale, $fallback);
        echo !is_array($result) ? $result : Str::encodeJson($result);
    }
}

if (!function_exists('rlang')) {
    /**
     * @deprecated Use the 't()' function instead, which provides the same functionality.
     */
    function rlang($key, array $replace = [], $locale = NULL, $fallback = true)
    {
        return Lang::get($key, $replace, $locale, $fallback);
    }
}

if (!function_exists('t')) {
    function t($key, array $replace = [], $locale = NULL, $fallback = true)
    {
        return Lang::get($key, $replace, $locale, $fallback);
    }
}
