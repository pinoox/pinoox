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

namespace Pinoox\Model;

use Pinoox\Component\Database\Model;
use Pinoox\Component\Date;
use Pinoox\Component\Helpers\HelperHeader;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Token;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Url;

class TokenModel extends Model
{
    const CREATED_AT = 'insert_date';
    const UPDATED_AT = null;
    public $incrementing = true;
    protected $primaryKey = ['app', 'token_key'];
    protected $table = 'pincore_token';

    protected $fillable = [
        'token_key',
        'token_name',
        'token_data',
        'user_id',
        'remote_url',
        'app',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'token_data' => 'json',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($token) {
            $token->app = $token->app ?? App::package();
            $token->ip = $token->ip ?? Url::clientIp();
            $token->user_agent = $token->user_agent?? Url::userAgent();
            $token->insert_date = Date::g('Y-m-d H:i:s');
            $token->expiration_date = Date::g('Y-m-d H:i:s', time() + Token::$lifeTime);
        });
    }
}
