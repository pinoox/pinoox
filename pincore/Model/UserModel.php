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
use Pinoox\Portal\App\App;
use Pinoox\Portal\Hash;

class UserModel extends Model
{

    const active = 'active';
    const suspend = 'suspend';
    const CREATED_AT = 'register_date';
    const UPDATED_AT = null;
    protected $table = 'pincore_user';
    public $incrementing = false;
    public $primaryKey = 'user_id';

    protected $fillable = [
        'session_id',
        'avatar_id',
        'app',
        'fname',
        'lname',
        'username',
        'password',
        'email',
        'status',
    ];

    protected $appends = ['full_name'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->app = $user->app ?: App::package();
            $user->status = $user->status ?: self::active;
            $user->password =  Hash::make($user->password);
        });
    }

    public function avatar(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FileModel::class, 'avatar_id', 'file_id');
    }

    public function getFullNameAttribute()
    {
        return $this->fname . ' ' . $this->lname;
    }
}
