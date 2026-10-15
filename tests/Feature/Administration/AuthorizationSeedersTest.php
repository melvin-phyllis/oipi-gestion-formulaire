<?php

namespace Tests\Feature\Administration;

use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\RolePermissionModel;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthorizationSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_risfm_roles_permissions_and_expected_matrix(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('adm_role_models', 4);
        $this->assertDatabaseCount('adm_permission_models', 27);
        $this->assertDatabaseCount('adm_role_permission_models', 58);

        $this->assertSame(
            count(PermissionCode::cases()),
            $this->activePermissionCount(RoleCode::SystemAdmin),
        );
        $this->assertSame(17, $this->activePermissionCount(RoleCode::RegistryManager));
        $this->assertSame(9, $this->activePermissionCount(RoleCode::SearchAgent));
        $this->assertSame(5, $this->activePermissionCount(RoleCode::ReadOnly));

        $this->assertTrue($this->roleHasPermission(
            RoleCode::RegistryManager,
            PermissionCode::MissionsAssign,
        ));
        $this->assertTrue($this->roleHasPermission(
            RoleCode::RegistryManager,
            PermissionCode::RegistryRegisterFound,
        ));
        $this->assertTrue($this->roleHasPermission(
            RoleCode::RegistryManager,
            PermissionCode::RegistryImport,
        ));
        $this->assertFalse($this->roleHasPermission(
            RoleCode::SearchAgent,
            PermissionCode::RegistryRegisterFound,
        ));
        $this->assertFalse($this->roleHasPermission(
            RoleCode::RegistryManager,
            PermissionCode::AuditView,
        ));
        $this->assertFalse($this->roleHasPermission(
            RoleCode::SearchAgent,
            PermissionCode::MissionsAssign,
        ));
        $this->assertTrue($this->roleHasPermission(
            RoleCode::SearchAgent,
            PermissionCode::MissionsRecordOwnResult,
        ));
        $this->assertTrue($this->roleHasPermission(
            RoleCode::RegistryManager,
            PermissionCode::MissionsReviewFound,
        ));
        $this->assertFalse($this->roleHasPermission(
            RoleCode::SearchAgent,
            PermissionCode::MissionsReviewFound,
        ));
        $this->assertFalse($this->roleHasPermission(
            RoleCode::ReadOnly,
            PermissionCode::RegistryCreate,
        ));
    }

    public function test_seeders_are_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('adm_role_models', 4);
        $this->assertDatabaseCount('adm_permission_models', 27);
        $this->assertDatabaseCount('adm_role_permission_models', 58);
    }

    private function activePermissionCount(RoleCode $roleCode): int
    {
        $role = RoleModel::query()
            ->where('role_code', $roleCode->value)
            ->firstOrFail();

        return RolePermissionModel::query()
            ->where('role_code_unique', $role->role_code_unique)
            ->where('is_active', true)
            ->count();
    }

    private function roleHasPermission(RoleCode $roleCode, PermissionCode $permissionCode): bool
    {
        $role = RoleModel::query()
            ->where('role_code', $roleCode->value)
            ->firstOrFail();
        $permission = PermissionModel::query()
            ->where('permission_code', $permissionCode->value)
            ->firstOrFail();

        return RolePermissionModel::query()
            ->where('role_code_unique', $role->role_code_unique)
            ->where('permission_code_unique', $permission->permission_code_unique)
            ->where('is_active', true)
            ->exists();
    }
}
