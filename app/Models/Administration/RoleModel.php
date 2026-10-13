<?php

namespace App\Models\Administration;

use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Administration\RoleModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'role_code',
    'role_name',
    'description',
    'is_system',
    'is_active',
])]
final class RoleModel extends Model
{
    /** @use HasFactory<RoleModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'adm_role_models';

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(
            RolePermissionModel::class,
            'role_code_unique',
            'role_code_unique',
        );
    }

    public function users(): HasMany
    {
        return $this->hasMany(
            UserModel::class,
            'role_code_unique',
            'role_code_unique',
        );
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            PermissionModel::class,
            'adm_role_permission_models',
            'role_code_unique',
            'permission_code_unique',
            'role_code_unique',
            'permission_code_unique',
        )
            ->withPivot([
                'uuid',
                'role_permission_code_unique',
                'is_active',
                'deleted_at',
            ])
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'role_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'ROL';
    }
}
