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

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pinoox\Component\Database\Model;
use Pinoox\Component\Transport\TransportConfig;
use Pinoox\Component\Transport\TransportScenario;
use Pinoox\Portal\Auth;
use Pinoox\Model\Scope\AppScope;
use Pinoox\Portal\App\App;
use Pinoox\Component\File\FileStorage;


/**
 * @property mixed $file_id
 */
class FileModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = Table::FILE;
    protected $primaryKey = 'file_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'hash_id',
        'user_id',
        'app',
        'file_group',
        'file_realname',
        'file_name',
        'file_ext',
        'file_path',
        'file_size',
        'file_access',
        'file_metadata',
    ];

    protected $casts = [
        'file_metadata' => 'array',
    ];

    protected $hidden = [
        'app'
    ];
    protected $appends = ['file_link', 'thumb_link'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function getFileLinkAttribute()
    {
        return FileStorage::url($this);
    }


    public function getThumbLinkAttribute()
    {
        return FileStorage::thumbUrl($this);
    }

    public static function setPackage(string $package): void
    {
        App::set('transport.' . TransportScenario::FILE_STORAGE, $package)->save();
        self::addAppGlobalScope();
    }

    public static function getPackage(): string
    {
        return TransportConfig::package(TransportScenario::FILE_STORAGE);
    }

    protected static function booted(): void
    {
        self::addAppGlobalScope();

        static::creating(function ($file) {
            $file->app = $file->app ?? self::getPackage();
            $file->user_id = $file->user_id ?? Auth::id();
        });

        static::deleted(function ($file) {
            FileStorage::delete($file);
        });
    }

    private static function addAppGlobalScope(): void
    {
        static::addGlobalScope('app', AppScope::for(
            fn (): array => TransportConfig::scopeValues(TransportScenario::FILE_STORAGE),
        ));
    }
}
