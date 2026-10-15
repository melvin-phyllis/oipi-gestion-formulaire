<?php

namespace Database\Seeders\Administration;

use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\RolePermissionModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class RoleManagementPermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call(PermissionSeeder::class);
            $role = RoleModel::query()->where('role_code', RoleCode::SystemAdmin->value)->sole();
            $permission = PermissionModel::query()->where('permission_code', PermissionCode::RolesManage->value)->sole();
            $grant = RolePermissionModel::withTrashed()->firstOrNew([
                'role_code_unique' => $role->role_code_unique,
                'permission_code_unique' => $permission->permission_code_unique,
            ]);
            $grant->is_active = true;
            $grant->deleted_at = null;
            $grant->save();
        });
    }
}
