<?php

namespace Database\Seeders\Administration;

use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\RolePermissionModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class AuditAccessPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $administratorRole = RoleModel::query()
                ->where('role_code', RoleCode::SystemAdmin->value)
                ->sole();
            $permission = PermissionModel::query()
                ->where('permission_code', PermissionCode::AuditView->value)
                ->sole();

            RolePermissionModel::withTrashed()
                ->where('permission_code_unique', $permission->permission_code_unique)
                ->where('role_code_unique', '!=', $administratorRole->role_code_unique)
                ->update(['is_active' => false]);

            $grant = RolePermissionModel::withTrashed()->firstOrNew([
                'role_code_unique' => $administratorRole->role_code_unique,
                'permission_code_unique' => $permission->permission_code_unique,
            ]);
            $grant->is_active = true;
            $grant->deleted_at = null;
            $grant->save();
        });
    }
}
