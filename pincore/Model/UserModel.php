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

use Illuminate\Database\Eloquent\Builder;
use Pinoox\Component\Database\Model;
use Pinoox\Model\Scope\AppScope;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Hash;

class UserModel extends Model
{

    const ACTIVE = 'active';
    const SUSPEND = 'suspend';
    const PENDING = 'pending';

    protected $table = 'pincore_user';
    public $incrementing = true;
    public $primaryKey = 'user_id';
    public $timestamps = true;

    protected $fillable = [
        'session_id',
        'avatar_id',
        'app',
        'fname',
        'lname',
        'username',
        'password',
        'group_key',
        'email',
        'mobile',
        'status',
    ];

    protected $appends = ['full_name'];

    protected $hidden = [
        'password', 'session_id', 'app'
    ];

    public static function hashPassword($password)
    {
        return Hash::make($password);
    }

    public static function updatePassword($user_id, $new_password)
    {
        $hashed_password = self::hashPassword($new_password);
        self::where('user_id', $user_id)->update(['password' => $hashed_password]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (UserModel $user) {
            $user->app = $user->app ?: self::getPackage();
            $user->status = $user->status ?: self::ACTIVE;
            $user->password = self::hashPassword($user->password);
        });

        static::deleting(function (UserModel $user) {
            $user->avatar()->delete();
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

    public static function setPackage(string $package): void
    {
        App::set('transport.user', $package)->save();
    }

    public static function getPackage(): string
    {
        $package = App::get('transport.user');
        return $package ?? App::package();
    }

    protected static function booted()
    {
        static::addGlobalScope('app', new AppScope(static::getPackage()));
    }
}
