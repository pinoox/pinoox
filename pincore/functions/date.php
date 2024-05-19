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

use Illuminate\Support\Carbon;
use Pinoox\Portal\Date;

if (!function_exists('now')) {
    /**
     * Create a new Carbon instance for the current time.
     */
    function now(DateTimeZone|string|null $tz = null): \Illuminate\Support\Carbon
    {
        return Date::now($tz);
    }
}

if (!function_exists('today')) {
    /**
     * Create a new Carbon instance for the current date.
     */
    function today(DateTimeZone|string $tz = null): Carbon
    {
        return Date::today($tz);
    }
}


if (!function_exists('format_date')) {
    function format_date($time = null, string $format = 'Y-m-d H:i:s', ?string $timezone = null): string
    {
        return Date::parse($time, $timezone)->format($format);
    }
}

if (!function_exists('carbon')) {
    function carbon($time = null, ?string $timezone = null): Carbon
    {
        return Date::parse($time, $timezone);
    }
}