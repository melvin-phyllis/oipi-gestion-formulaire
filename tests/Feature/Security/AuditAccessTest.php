<?php

namespace Tests\Feature\Security;

use App\Dto\Administration\UpdateRolePermissionsDTO;
use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Enums\Audit\AuditAction;
use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\RolePermissionModel;
use App\Models\Administration\UserModel;
use App\Services\Administration\RolePermissionManagementService;
use App\Services\Audit\AuditLogger;
use Database\Seeders\Administration\AuditAccessPermissionSeeder;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class AuditAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_active_system_administrator_can_consult_audit_and_see_its_menu(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        app(AuditLogger::class)->srv_record(AuditAction::FormCreated, 'Privileged audit entry.');

        $this->assertTrue($administrator->can(PermissionCode::AuditView->value));
        $this->signIn($administrator);
        $this->get(route('audit.index'))->assertOk()->assertSee('Privileged audit entry.');
        $this->get(route('dashboard'))->assertOk()->assertSee(route('audit.index'));
    }

    public function test_all_ordinary_roles_are_denied_even_with_an_active_audit_grant(): void
    {
        foreach ([RoleCode::RegistryManager, RoleCode::SearchAgent, RoleCode::ReadOnly] as $roleCode) {
            $role = $this->role($roleCode);
            $this->grant($role, PermissionCode::AuditView);
            $user = $this->user($roleCode);

            $this->assertFalse($user->can(PermissionCode::AuditView->value), $roleCode->value);
            $this->signIn($user);
            $this->get(route('audit.index'))->assertForbidden();
            $this->getJson(route('audit.index'))->assertForbidden();
        }
    }

    public function test_an_administrator_label_does_not_authorize_a_custom_role(): void
    {
        $role = RoleModel::factory()->create(['role_name' => 'Administrateur système']);
        $this->grant($role, PermissionCode::AuditView);
        $user = UserModel::factory()->create(['role_code_unique' => $role->role_code_unique]);

        $this->assertFalse($user->can(PermissionCode::AuditView->value));
        $this->signIn($user);
        $this->getJson(route('audit.index'))->assertForbidden();
    }

    public function test_legacy_audit_grant_exposes_neither_menu_nor_dashboard_activity(): void
    {
        $this->grant($this->role(RoleCode::RegistryManager), PermissionCode::AuditView);
        $manager = $this->user(RoleCode::RegistryManager);
        app(AuditLogger::class)->srv_record(AuditAction::FormCreated, 'Privileged audit entry.');
        $this->signIn($manager);

        $this->get(route('dashboard'))->assertOk()
            ->assertDontSee(route('audit.index'))
            ->assertDontSee('Privileged audit entry.')
            ->assertViewHas('capabilities', fn (array $value): bool => $value['viewOperationalActivity'] === false)
            ->assertViewHas('recentActivity', fn ($activities): bool => $activities->isEmpty());
        $this->getJson(route('audit.index'))->assertForbidden()->assertDontSee('Privileged audit entry.');
    }

    public function test_audit_assignment_is_rejected_atomically_for_every_non_administrator_role(): void
    {
        $this->signIn($this->user(RoleCode::SystemAdmin));

        foreach ([RoleCode::RegistryManager, RoleCode::SearchAgent, RoleCode::ReadOnly] as $roleCode) {
            $role = $this->role($roleCode);
            $before = $this->grantsSnapshot($role);

            $this->putJson(route('roles.permissions.update', $role), [
                'permissions' => [PermissionCode::RegistryView->value, PermissionCode::AuditView->value],
            ])->assertUnprocessable()->assertJsonValidationErrors('permissions')
                ->assertJsonPath('errors.permissions.0', 'La consultation du journal d’audit est réservée aux administrateurs système.');

            $this->assertSame($before, $this->grantsSnapshot($role));
        }

        $this->assertDatabaseMissing('aud_audit_log_models', ['action_code' => AuditAction::RolePermissionsUpdated->value]);
    }

    public function test_service_rejects_audit_assignment_without_an_http_request(): void
    {
        $role = $this->role(RoleCode::RegistryManager);
        $before = $this->grantsSnapshot($role);

        try {
            app(RolePermissionManagementService::class)->srv_update(
                $role,
                UpdateRolePermissionsDTO::fromValidated(['permissions' => [PermissionCode::AuditView->value]]),
                $this->user(RoleCode::SystemAdmin),
            );
            $this->fail('Le service devait refuser l’attribution de audit_view.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('permissions', $exception->errors());
        }

        $this->assertSame($before, $this->grantsSnapshot($role));
    }

    public function test_inactive_system_administrator_is_denied(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        $administrator->update(['is_active' => false]);

        $this->assertFalse($administrator->can(PermissionCode::AuditView->value));
        $this->signIn($administrator);
        $this->getJson(route('audit.index'))->assertForbidden();
    }

    public function test_inactive_administrator_role_is_denied(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        $this->role(RoleCode::SystemAdmin)->update(['is_active' => false]);

        $this->assertFalse($administrator->can(PermissionCode::AuditView->value));
        $this->signIn($administrator);
        $this->getJson(route('audit.index'))->assertForbidden();
    }

    public function test_soft_deleted_administrator_role_is_denied(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        $this->role(RoleCode::SystemAdmin)->delete();

        $this->assertFalse($administrator->can(PermissionCode::AuditView->value));
    }

    public function test_soft_deleted_administrator_account_is_denied(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        $administrator->delete();

        $this->assertFalse($administrator->can(PermissionCode::AuditView->value));
    }

    public function test_administrator_role_does_not_bypass_a_revoked_permission(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        $this->grant($this->role(RoleCode::SystemAdmin), PermissionCode::AuditView)->update(['is_active' => false]);

        $this->assertFalse($administrator->can(PermissionCode::AuditView->value));
        $this->signIn($administrator);
        $this->getJson(route('audit.index'))->assertForbidden();
    }

    public function test_administrator_role_does_not_bypass_an_inactive_permission_catalog_entry(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        PermissionModel::query()->where('permission_code', PermissionCode::AuditView->value)->update(['is_active' => false]);

        $this->assertFalse($administrator->can(PermissionCode::AuditView->value));
    }

    public function test_changing_role_revokes_audit_despite_a_cached_role_relation_and_legacy_grant(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        $administrator->load('role');
        $managerRole = $this->role(RoleCode::RegistryManager);
        $this->grant($managerRole, PermissionCode::AuditView);
        $administrator->update(['role_code_unique' => $managerRole->role_code_unique]);

        $this->assertSame(RoleCode::SystemAdmin->value, $administrator->role->role_code);
        $this->assertFalse($administrator->can(PermissionCode::AuditView->value));
        $this->signIn($administrator);
        $this->getJson(route('audit.index'))->assertForbidden();
    }

    public function test_focused_seeder_revokes_legacy_grants_and_preserves_other_permissions_and_accounts(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        $manager = $this->user(RoleCode::RegistryManager);
        $passwords = [$administrator->password, $manager->password];
        $ordinaryGrant = $this->grant($this->role(RoleCode::RegistryManager), PermissionCode::UsersManage);
        $grantSnapshot = $ordinaryGrant->refresh()->getAttributes();
        $legacyGrants = collect([
            $this->grant($this->role(RoleCode::RegistryManager), PermissionCode::AuditView),
            $this->grant($this->role(RoleCode::SearchAgent), PermissionCode::AuditView),
            $this->grant($this->role(RoleCode::ReadOnly), PermissionCode::AuditView),
        ]);
        $customRole = RoleModel::factory()->create();
        $legacyGrants->push($this->grant($customRole, PermissionCode::AuditView));
        $customRole->delete();

        $this->seed(AuditAccessPermissionSeeder::class);

        foreach ($legacyGrants as $grant) {
            $this->assertFalse($grant->fresh()->is_active);
        }
        $this->assertSame($grantSnapshot, $ordinaryGrant->fresh()->getAttributes());
        $this->assertSame($passwords, [$administrator->fresh()->password, $manager->fresh()->password]);
        $this->assertDatabaseCount('adm_user_models', 2);
        $this->assertTrue($administrator->can(PermissionCode::AuditView->value));
    }

    public function test_focused_seeder_restores_existing_administrator_audit_grant_without_duplicates(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        $grant = $this->grant($this->role(RoleCode::SystemAdmin), PermissionCode::AuditView);
        $grant->delete();
        $count = RolePermissionModel::withTrashed()->count();

        $this->seed(AuditAccessPermissionSeeder::class);
        $this->seed(AuditAccessPermissionSeeder::class);

        $this->assertSame($count, RolePermissionModel::withTrashed()->count());
        $restored = RolePermissionModel::query()->where('uuid', $grant->uuid)->sole();
        $this->assertTrue($restored->is_active);
        $this->assertTrue($administrator->can(PermissionCode::AuditView->value));
    }

    public function test_standard_role_permission_seeder_also_revokes_all_non_administrator_audit_grants(): void
    {
        $grants = collect();
        foreach ([RoleCode::RegistryManager, RoleCode::SearchAgent, RoleCode::ReadOnly] as $roleCode) {
            $grants->push($this->grant($this->role($roleCode), PermissionCode::AuditView));
        }

        $this->seed(RolePermissionSeeder::class);

        foreach ($grants as $grant) {
            $this->assertFalse($grant->fresh()->is_active);
        }
    }

    private function role(RoleCode $roleCode): RoleModel
    {
        return RoleModel::query()->where('role_code', $roleCode->value)->sole();
    }

    private function user(RoleCode $roleCode): UserModel
    {
        return UserModel::factory()->create([
            'role_code_unique' => $this->role($roleCode)->role_code_unique,
            'service_code_unique' => null,
        ]);
    }

    private function grant(RoleModel $role, PermissionCode $permissionCode): RolePermissionModel
    {
        $permission = PermissionModel::query()->where('permission_code', $permissionCode->value)->sole();
        $grant = RolePermissionModel::withTrashed()->firstOrNew([
            'role_code_unique' => $role->role_code_unique,
            'permission_code_unique' => $permission->permission_code_unique,
        ]);
        $grant->is_active = true;
        $grant->deleted_at = null;
        $grant->save();

        return $grant;
    }

    /** @return array<int, array<string, mixed>> */
    private function grantsSnapshot(RoleModel $role): array
    {
        return RolePermissionModel::withTrashed()->where('role_code_unique', $role->role_code_unique)
            ->orderBy('id')->get()->map(fn (RolePermissionModel $grant): array => $grant->getAttributes())->all();
    }

    private function signIn(UserModel $user): void
    {
        $this->actingAs($user)->withSession(['auth_session_version' => $user->session_version]);
    }
}
