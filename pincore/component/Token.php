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
use Pinoox\Component\Helpers\Str;
use Pinoox\Portal\App\App;
use Pinoox\Model\TokenModel;

class Token
{

    public static $lifeTime = 86400;
    private static $app;
    private static $token_key = null;

    /**
     * Set token of specific app
     *
     * @param $packageName
     */
    public static function app($packageName)
    {
        self::$app = $packageName;
    }

    public static function __constructStatic()
    {
        TokenModel::delete_all_expired();
    }

    public static function lifeTime($lifeTime, $unitTime = null)
    {
        if ($unitTime === 'min') $lifeTime = $lifeTime * 60;
        else if ($unitTime === 'hour') $lifeTime = $lifeTime * 60 * 60;
        else if ($unitTime === 'day') $lifeTime = $lifeTime * 60 * 60 * 24;
        self::$lifeTime = $lifeTime;
    }

    public static function generate($data, $name = null, $user_id = null, $token_key = null)
    {
        $data = (is_array($data)) ? $data : [$data];
        self::$token_key = empty($token_key) ? self::generateUniqueKey() : $token_key;
        TokenModel::insert([
            'token_key' => self::$token_key,
            'token_name' => $name,
            'token_data' => $data,
            'user_id' => $user_id,
        ]);
        return self::$token_key;
    }

    private static function generateUniqueKey($type = 1)
    {
        $time = microtime();
        $time = str_replace(['.', ' '], '', $time);
        $length = rand(10, 40);
        $str = str_shuffle('abcefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
        $text = substr($str, 0, $length);
        $ip = HelperHeader::getIP();
        $token_key = md5($time . $text . $ip);
        $result = null;
        switch ($type) {
            case 2:
                $length = rand(16, 20);
                $str1 = substr($str, 10, $length);
                $length = rand(26, 30);
                $str2 = substr($str, 20, $length);
                $result = $str1 . $token_key . $str2;
                break;
            case 3:
                $token_key = sha1($text . $token_key . $time);
                $token_key = str_shuffle($text . $time) . $token_key;
                $result = $token_key;
                break;
            default:
                $result = $token_key;
                break;
        }

        return $result;
    }

    public static function getData($token_key)
    {
        $token = TokenModel::where('token_key',$token_key)
            ->where('app',self::getApp())
            ->first();

        return !empty($token) ? Str::decodeJson($token->token_data) : null;
    }

    public static function delete($token_key)
    {
        return TokenModel::where('token_key',$token_key)
            ->where('app',self::getApp())
            ->delete();
    }

    public static function get($token_key)
    {
        $token = TokenModel::where('token_key',$token_key)
            ->where('app',self::getApp())
            ->first();
        if ($token)
            $token->token_data = Str::decodeJson($token->token_data);

        return $token->toArray();
    }

    public static function setData($token_key, $token_data, $UpdateLifetime = false)
    {
        $values = [
            'token_data' => Str::encodeJson($token_data),
        ];
        if ($UpdateLifetime)
            $values['expiration_date'] = Date::g('Y-m-d H:i:s', time() + Token::$lifeTime);

        return TokenModel::where('token_key',$token_key)
            ->where('app',self::getApp())
            ->update($values);
    }

    public static function updateLifetime($token_key)
    {
        return  TokenModel::where('token_key',$token_key)
            ->where('app',self::getApp())
            ->update([
                'expiration_date' => Date::g('Y-m-d H:i:s', time() + Token::$lifeTime),
            ]);
    }

    public static function changeKey($old_token_key, $UpdateLifetime = false, $app = true)
    {
        self::$token_key = self::generateUniqueKey();

        $token = TokenModel::where('token_key',$old_token_key);
        if ($app)
            $token->where('app', Token::getApp());
        $token->update([
                'token_key' =>self::$token_key,
            ]);

        return self::$token_key;
    }

    /**
     * Get App name
     *
     * @return null|string
     */
    public static function getApp()
    {
        return (!empty(self::$app)) ? self::$app : App::package();
    }
}
