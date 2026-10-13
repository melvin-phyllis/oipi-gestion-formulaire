<?php

namespace App\Models\Administration;

use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Administration\RolePermissionModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'role_code_unique',
    'permission_code_unique',
    'is_active',
])]
final class RolePermissionModel extends Model
{
    /** @use HasFactory<RolePermissionModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'adm_role_permission_models';

    public function role(): BelongsTo
    {
        return $this->belongsTo(
            RoleModel::class,
            'role_code_unique',
            'role_code_unique',
        );
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(
            PermissionModel::class,
            'permission_code_unique',
            'permission_code_unique',
        );
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'role_permission_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'RPE';
    }
}
