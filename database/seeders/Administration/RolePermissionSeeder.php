<?php

namespace Database\Seeders\Administration;

use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\RolePermissionModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matrix = [
            RoleCode::SystemAdmin->value => PermissionCode::cases(),
            RoleCode::RegistryManager->value => [
                PermissionCode::RegistryView,
                PermissionCode::RegistryCreate,
                PermissionCode::RegistryRegisterFound,
                PermissionCode::RegistryUpdateMetadata,
                PermissionCode::MissionsAssign,
                PermissionCode::MissionsRecordOwnResult,
                PermissionCode::MissionsReviewFound,
                PermissionCode::RegistryFinalize,
                PermissionCode::AttachmentsAddOwn,
                PermissionCode::AttachmentsDeleteOwn,
                PermissionCode::RegistryExport,
                PermissionCode::RegistryImport,
                PermissionCode::DashboardView,
                PermissionCode::AnalyticsView,
                PermissionCode::RegistrySearch,
                PermissionCode::NotificationsView,
                PermissionCode::ProfileUpdate,
            ],
            RoleCode::SearchAgent->value => [
                PermissionCode::RegistryView,
                PermissionCode::RegistryCreate,
                PermissionCode::MissionsRecordOwnResult,
                PermissionCode::AttachmentsAddOwn,
                PermissionCode::AttachmentsDeleteOwn,
                PermissionCode::DashboardView,
                PermissionCode::RegistrySearch,
                PermissionCode::NotificationsView,
                PermissionCode::ProfileUpdate,
            ],
            RoleCode::ReadOnly->value => [
                PermissionCode::RegistryView,
                PermissionCode::DashboardView,
                PermissionCode::RegistrySearch,
                PermissionCode::NotificationsView,
                PermissionCode::ProfileUpdate,
            ],
        ];

        DB::transaction(function () use ($matrix): void {
            $roles = RoleModel::query()->get()->keyBy('role_code');
            $permissions = PermissionModel::query()->get()->keyBy('permission_code');

            foreach ($matrix as $roleCode => $permissionCodes) {
                $role = $roles->get($roleCode);

                if (! $role instanceof RoleModel) {
                    throw new RuntimeException("The role [$roleCode] must be seeded first.");
                }

                foreach ($permissionCodes as $permissionCode) {
                    $permission = $permissions->get($permissionCode->value);

                    if (! $permission instanceof PermissionModel) {
                        throw new RuntimeException("The permission [$permissionCode->value] must be seeded first.");
                    }

                    RolePermissionModel::query()->firstOrCreate([
                        'role_code_unique' => $role->role_code_unique,
                        'permission_code_unique' => $permission->permission_code_unique,
                    ], [
                        'is_active' => true,
                    ]);
                }
            }

            $this->call(AuditAccessPermissionSeeder::class);
        });
    }
}
