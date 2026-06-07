<?php

namespace Pinoox\System\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Pinoox\Component\Database\Model;
use Pinoox\System\Model\Scope\AppScope;
use Pinoox\Portal\App\App;

class RoleModel extends Model
{
    protected $table = Table::ROLE;
    protected $primaryKey = 'role_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'app',
        'role_key',
        'name',
        'description',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            PermissionModel::class,
            Table::ROLE_PERMISSION,
            'role_id',
            'permission_id',
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            UserModel::class,
            Table::USER_ROLE,
            'role_id',
            'user_id',
        );
    }

    public static function setPackage(string $package): void
    {
        App::set('transport.access', $package)->save();
        self::addAppGlobalScope();
    }

    protected static function booted(): void
    {
        static::addGlobalScope('app', new AppScope(static::getPackage()));

        static::creating(function (RoleModel $role) {
            $role->app = $role->app ?? static::getPackage();
        });
    }

    public static function getPackage(): string
    {
        return App::get('transport.access') ?? App::get('transport.user') ?? App::package();
    }

    private static function addAppGlobalScope(): void
    {
        static::addGlobalScope('app', new AppScope(static::getPackage()));
    }
}
