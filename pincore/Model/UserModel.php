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


/**
 * @property mixed $user_id
 * @property mixed $fname
 * @property mixed $lname
 * @property mixed $email
 * @property string $status
 * @property string $password
 * @property string|null $app
 */
class UserModel extends Model
{

    const active = 'active';
    const suspend = 'suspend';
    const CREATED_AT = 'register_date';
    const UPDATED_AT = null;
    protected $table = 'pincore_user';
    public $incrementing = true;
    protected $primaryKey = 'user_id';

    protected $hidden = ['password', 'session_id'];
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
            $user->app = $user->app ?: App::package();
            $user->status = $user->status ?: self::active;
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
}
