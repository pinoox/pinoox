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

namespace Pinoox\Component;

use Pinoox\Component\Helpers\HelperHeader;
use Pinoox\Component\Kernel\BootInterface;
use Pinoox\Model\TokenModel;

class Token implements BootInterface
{

    public static $lifeTime = 86400 * 30;// Default 1 day in seconds
    private static $token_key = null;

    public static function __register()
    {
        self::deleteAllExpired();
    }

    public static function deleteAllExpired()
    {
        $now = Date::g('Y-m-d H:i:s');
        return TokenModel::withoutGlobalScopes(['app'])->where('expiration_date', '<', $now)->delete();
    }

    /**
     * Set the lifetime of the token.
     *
     * @param int $lifeTime
     * @param string|null $unitTime ('min', 'hour', 'day')
     */
    public static function lifeTime($lifeTime, $unitTime = null)
    {
        if ($unitTime === 'min') $lifeTime = $lifeTime * 60;
        else if ($unitTime === 'hour') $lifeTime = $lifeTime * 60 * 60;
        else if ($unitTime === 'day') $lifeTime = $lifeTime * 60 * 60 * 24;
        self::$lifeTime = $lifeTime;
    }

    /**
     * Calculate the expiration date based on the set lifetime.
     *
     * @return string
     */
    private static function calculateExpirationDate(): string
    {
        return date('Y-m-d H:i:s', time() + self::$lifeTime);
    }

    public static function generate($data, $name = null, $user_id = null, $token_key = null)
    {
        $data = is_array($data) ? $data : [$data];
        self::$token_key = empty($token_key) ? self::generateUniqueKey() : $token_key;
 
        TokenModel::create([
            'token_key' => self::$token_key,
            'token_name' => $name,
            'token_data' => $data,
            'user_id' => $user_id,
            'expiration_date' => self::calculateExpirationDate(),
        ]);

        return self::$token_key;
    }

    private static function generateUniqueKey($type = 1): string
    {
        $time = microtime();
        $time = str_replace(['.', ' '], '', $time);
        $length = rand(10, 40);
        $str = str_shuffle('abcefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
        $text = substr($str, 0, $length);
        $ip = HelperHeader::getIP();
        $token_key = md5($time . $text . $ip);

        return match ($type) {
            2 => substr($str, 10, rand(16, 20)) . $token_key . substr($str, 20, rand(26, 30)),
            3 => str_shuffle($text . $time) . sha1($text . $token_key . $time),
            default => $token_key,
        };
    }

    public static function getData($token_key)
    {
        $token = TokenModel::where('token_key', $token_key)->first();
        return !empty($token) ? $token->token_data : null;
    }

    public static function delete($token_key)
    {
        return TokenModel::where('token_key', $token_key)->delete();
    }

    public static function get($token_key)
    {
        $token = TokenModel::where('token_key', $token_key)->first();
        return !empty($token) ? $token->toArray() : null;
    }

    public static function setData($token_key, $token_data, $UpdateLifetime = false)
    {
        $values = ['token_data' => $token_data];
        if ($UpdateLifetime) {
            $values['expiration_date'] = self::calculateExpirationDate();
        }

        return TokenModel::where('token_key', $token_key)->update($values);
    }

    public static function updateLifetime($token_key)
    {
        return TokenModel::where('token_key', $token_key)->update([
            'expiration_date' => self::calculateExpirationDate(),
        ]);
    }


    public static function changeKey($old_token_key, $UpdateLifetime = false, $app = true)
    {
        self::$token_key = self::generateUniqueKey();

        $token = TokenModel::where('token_key', $old_token_key);
        if (!$app) {
            $token->withoutGlobalScope('app');
        }

        $values = ['token_key' => self::$token_key];
        if ($UpdateLifetime) {
            $values['expiration_date'] = self::calculateExpirationDate();
        }

        $token->update($values);

        return self::$token_key;
    }
}
