<?php

namespace Pinoox\System\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Pinoox\Component\Database\Model;
use Pinoox\System\Model\Scope\AppScope;
use Pinoox\Portal\App\App;

class PermissionModel extends Model
{
    protected $table = Table::PERMISSION;
    protected $primaryKey = 'permission_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'app',
        'permission_key',
        'name',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            RoleModel::class,
            Table::ROLE_PERMISSION,
            'permission_id',
            'role_id',
        );
    }

    protected static function booted(): void
    {
        static::addGlobalScope('app', new AppScope(static::getPackage()));

        static::creating(function (PermissionModel $permission) {
            $permission->app = $permission->app ?? static::getPackage();
        });
    }

    public static function getPackage(): string
    {
        return App::get('transport.access') ?? App::get('transport.user') ?? App::package();
    }
}
