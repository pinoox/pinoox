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

namespace pinoox\model;

use pinoox\component\Date;
use pinoox\component\HelperHeader;
use pinoox\component\HelperString;
use pinoox\component\Token;

class TokenModel extends PinooxDatabase
{

    public static function fetch_by_key($token_key)
    {
        self::$db->where('app', Token::getApp());
        self::$db->where('token_key', $token_key);
        return self::$db->getOne(self::token);
    }

    public static function update_user_id($token_key,$user_id)
    {
        self::$db->where('app', Token::getApp());
        self::$db->where('token_key', $token_key);
        return self::$db->update(self::token,[
            'user_id' => $user_id,
        ]);
    }

    public static function fetch_by_key_without_app($token_key)
    {
        self::$db->where('token_key', $token_key);
        return self::$db->getOne(self::token);
    }

    public static function delete_all_expired()
    {
        $now = Date::g('Y-m-d H:i:s');
        self::$db->where('expiration_date', $now, '<');
        return self::$db->delete(self::token);
    }

    public static function delete_by_key($token_key)
    {
        self::$db->where('app', Token::getApp());
        self::$db->where('token_key', $token_key);
        return self::$db->delete(self::token);
    }

    public static function delete_by_app($app)
    {
        $app = is_null($app) ? Token::getApp() : $app;
        self::$db->where('app', $app);
        return self::$db->delete(self::token);
    }

    public static function insert($form)
    {
        self::$db->insert(self::token, [
            'token_key' => $form['token_key'],
            'token_data' => HelperString::encodeJson($form['token_data']),
            'token_name' => isset($form['token_name']) ? $form['token_name'] : null,
            'app' => Token::getApp(),
            'user_id' => isset($form['user_id']) ? $form['user_id'] : null,
            'ip' => HelperHeader::getIP(),
            'user_agent' => HelperHeader::getUserAgent(),
            'insert_date' => Date::g('Y-m-d H:i:s'),
            'expiration_date' => Date::g('Y-m-d H:i:s', time() + Token::$lifeTime),
        ]);
    }

    public static function update_data($token_key, $token_data, $UpdateLifetime = false)
    {
        self::$db->where('app', Token::getApp());
        self::$db->where('token_key', $token_key);
        $values = [
            'token_data' => HelperString::encodeJson($token_data),
        ];
        if ($UpdateLifetime)
            $values['expiration_date'] = Date::g('Y-m-d H:i:s', time() + Token::$lifeTime);
        return self::$db->update(self::token, $values);
    }

    public static function update_key($old_token_key, $token_key, $UpdateLifetime = false, $app = true)
    {
        if ($app)
            self::$db->where('app', Token::getApp());

        self::$db->where('token_key', $old_token_key);
        $values = [
            'token_key' => $token_key,
        ];
        if ($UpdateLifetime)
            $values['expiration_date'] = Date::g('Y-m-d H:i:s', time() + Token::$lifeTime);
        return self::$db->update(self::token, $values);
    }

    public static function update_lifetime($token_key)
    {
        self::$db->where('app', Token::getApp());
        self::$db->where('token_key', $token_key);
        return self::$db->update(self::token, [
            'expiration_date' => Date::g('Y-m-d H:i:s', time() + Token::$lifeTime),
        ]);
    }
}
    
