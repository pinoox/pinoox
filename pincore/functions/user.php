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


use Pinoox\Component\User;

if (!function_exists('user')) {
    function user(string $key = null): mixed
    {
        return User::get($key);
    }
}

if (!function_exists('isLogin')) {
    function isLogin(): bool
    {
        return User::isLoggedIn();
    }
}