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

class Security
{
    public static function passHash($password, $alg = 'md5', $salt = null)
    {
        if ($salt != null)
            $password = $password . $salt;
        if ($alg == 'bcrypt') {
            return crypt($password, $salt);
        } else if ($alg == 'md5') {
            return md5($password);
        }

    }

    public static function passVerify($password, $hash, $alg = 'md5')
    {
        if ($alg == 'bcrypt') {
            return hash_equals($password, crypt($password, $hash));
        } else {
            return self::passHash($password,$alg) == $hash;
        }
    }



}