<?php

namespace Pinoox\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Pinoox\Component\Database\Model;
use Pinoox\Component\Transport\TransportConfig;
use Pinoox\Component\Transport\TransportScenario;
use Pinoox\Model\Scope\AppScope;

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
        static::addGlobalScope('app', AppScope::for(
            fn (): array => TransportConfig::scopeValues(TransportScenario::ACCESS_TABLE),
        ));

        static::creating(function (PermissionModel $permission) {
            $permission->app = $permission->app ?? static::getPackage();
        });
    }

    public static function getPackage(): string
    {
        return TransportConfig::package(TransportScenario::ACCESS_TABLE);
    }
}
