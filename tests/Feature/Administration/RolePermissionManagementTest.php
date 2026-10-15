<?php

namespace Tests\Feature\Administration;

use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Enums\Audit\AuditAction;
use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\RolePermissionModel;
use App\Models\Administration\UserModel;
use App\Models\Audit\AuditLogModel;
use App\Models\Organization\ServiceModel;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Database\Seeders\Organization\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $administrator;

    private ServiceModel $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ServiceSeeder::class);

        $this->service = ServiceModel::query()
            ->where('service_code', 'SDSID')
            ->firstOrFail();
        $this->administrator = $this->createUserForRole(RoleCode::SystemAdmin);
    }

    public function test_role_permission_management_requires_the_roles_manage_permission(): void
    {
        $searchAgent = $this->createUserForRole(RoleCode::SearchAgent);

        $this->actingAsCurrent($searchAgent)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_permissions_can_be_replaced_and_are_audited(): void
    {
        $role = $this->role(RoleCode::RegistryManager);
        $selectedPermissions = [
            PermissionCode::RegistryView->value,
            PermissionCode::DashboardView->value,
            PermissionCode::UsersManage->value,
        ];

        $this->actingAsCurrent($this->administrator)
            ->put(route('roles.permissions.update', $role), [
                'role_uuid' => $role->uuid,
                'permissions' => $selectedPermissions,
            ])
            ->assertRedirect(route('roles.index').'#role-'.$role->uuid)
            ->assertSessionHasNoErrors();

        sort($selectedPermissions);
        $this->assertSame(
            $selectedPermissions,
            $this->activePermissionCodes($role),
        );

        $audit = AuditLogModel::query()
            ->where('action_code', AuditAction::RolePermissionsUpdated->value)
            ->sole();

        $this->assertSame($role->uuid, $audit->subject_uuid);
        $this->assertSame($role->role_code_unique, $audit->subject_code_unique);
        $this->assertSame(
            $this->administrator->user_code_unique,
            $audit->actor_user_code_unique,
        );
        $this->assertContains(
            PermissionCode::UsersManage->value,
            $audit->metadata['added_permissions'],
        );
        $this->assertContains(
            PermissionCode::MissionsAssign->value,
            $audit->metadata['removed_permissions'],
        );
    }

    public function test_updated_permissions_are_effective_on_the_next_request(): void
    {
        $role = $this->role(RoleCode::SearchAgent);
        $searchAgent = $this->createUserForRole(RoleCode::SearchAgent);

        $this->actingAsCurrent($this->administrator)
            ->put(route('roles.permissions.update', $role), [
                'permissions' => [PermissionCode::UsersManage->value],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAsCurrent($searchAgent)
            ->put(route('roles.permissions.update', $role), [
                'permissions' => [PermissionCode::RegistryView->value],
            ])
            ->assertForbidden();

        $this->actingAsCurrent($searchAgent)->get(route('users.index'))->assertOk();
        $this->assertSame([PermissionCode::UsersManage->value], $this->activePermissionCodes($role));
    }

    public function test_system_administrator_permissions_are_protected(): void
    {
        $role = $this->role(RoleCode::SystemAdmin);
        $initialPermissions = $this->activePermissionCodes($role);

        $this->actingAsCurrent($this->administrator)
            ->put(route('roles.permissions.update', $role), [
                'permissions' => [PermissionCode::RegistryView->value],
            ])
            ->assertSessionHasErrors('permissions');

        $this->assertSame($initialPermissions, $this->activePermissionCodes($role));
    }

    public function test_unknown_permissions_are_rejected(): void
    {
        $role = $this->role(RoleCode::RegistryManager);
        $initialPermissions = $this->activePermissionCodes($role);

        $this->actingAsCurrent($this->administrator)
            ->put(route('roles.permissions.update', $role), [
                'permissions' => ['unknown_permission'],
            ])
            ->assertSessionHasErrors('permissions.0');

        $this->assertSame($initialPermissions, $this->activePermissionCodes($role));
    }

    public function test_running_the_seeder_does_not_overwrite_an_admin_configuration(): void
    {
        $role = $this->role(RoleCode::RegistryManager);
        $permission = PermissionModel::query()
            ->where('permission_code', PermissionCode::MissionsAssign->value)
            ->firstOrFail();

        RolePermissionModel::query()
            ->where('role_code_unique', $role->role_code_unique)
            ->where('permission_code_unique', $permission->permission_code_unique)
            ->update(['is_active' => false]);

        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseHas('adm_role_permission_models', [
            'role_code_unique' => $role->role_code_unique,
            'permission_code_unique' => $permission->permission_code_unique,
            'is_active' => false,
        ]);
    }

    public function test_roles_index_view_is_rendered_with_tabler_and_oipi_rules(): void
    {
        $systemAdmin = $this->role(RoleCode::SystemAdmin);
        $registryManager = $this->role(RoleCode::RegistryManager);

        $response = $this->actingAsCurrent($this->administrator)
            ->get(route('roles.index'))
            ->assertOk();

        $response->assertSee('Rôles & Permissions', false);
        $response->assertSee('id="role-'.$systemAdmin->uuid.'"', false);
        $response->assertSee('id="role-'.$registryManager->uuid.'"', false);
        $response->assertSee('name="role_uuid"', false);
        $response->assertSee('type="hidden" name="permissions[]" value=""', false);
        $response->assertSee('id="btn-save-'.$registryManager->uuid.'"', false);
        $response->assertDontSee('id="btn-save-'.$systemAdmin->uuid.'"', false);
        $response->assertSee('Protégé (Lecture seule)');
    }

    /** @return list<string> */
    private function activePermissionCodes(RoleModel $role): array
    {
        return RolePermissionModel::query()
            ->with('permission')
            ->where('role_code_unique', $role->role_code_unique)
            ->where('is_active', true)
            ->get()
            ->pluck('permission.permission_code')
            ->sort()
            ->values()
            ->all();
    }

    private function createUserForRole(RoleCode $roleCode): UserModel
    {
        return UserModel::factory()->create([
            'role_code_unique' => $this->role($roleCode)->role_code_unique,
            'service_code_unique' => $this->service->service_code_unique,
            'must_change_password' => false,
        ]);
    }

    private function role(RoleCode $roleCode): RoleModel
    {
        return RoleModel::query()
            ->where('role_code', $roleCode->value)
            ->firstOrFail();
    }

    private function actingAsCurrent(UserModel $user): static
    {
        return $this->actingAs($user)->withSession([
            'auth_session_version' => $user->session_version,
        ]);
    }
}
