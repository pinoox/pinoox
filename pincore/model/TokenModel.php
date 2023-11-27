<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\model;

use pinoox\component\database\Model;
use pinoox\component\Date;
use pinoox\component\Helpers\HelperHeader;
use pinoox\component\Helpers\Str;
use pinoox\component\Token;

class TokenModel extends Model
{
    const CREATED_AT = 'insert_date';
    const UPDATED_AT = null;
    public $incrementing = false;
    public $primaryKey = 'user_id';
    protected $table = 'pincore_token';

    public static function delete_all_expired()
    {
        $now = Date::g('Y-m-d H:i:s');
        return self::where('expiration_date','<',$now)->delete();
    }

    public static function insert(array $attributes)
    {
        self::create([
            'token_key' => $attributes['token_key'],
            'token_data' => Str::encodeJson($attributes['token_data']),
            'token_name' => isset($attributes['token_name']) ? $attributes['token_name'] : null,
            'app' => Token::getApp(),
            'user_id' => isset($attributes['user_id']) ? $attributes['user_id'] : null,
            'ip' => HelperHeader::getIP(),
            'user_agent' => HelperHeader::getUserAgent(),
            'insert_date' => Date::g('Y-m-d H:i:s'),
            'expiration_date' => Date::g('Y-m-d H:i:s', time() + Token::$lifeTime),
        ]);
    }
}
