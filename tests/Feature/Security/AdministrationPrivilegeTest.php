<?php

namespace Tests\Feature\Security;

use App\Dto\Administration\UpdateRolePermissionsDTO;
use App\Dto\Administration\UpdateUserDTO;
use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\RolePermissionModel;
use App\Models\Administration\UserModel;
use App\Services\Administration\RolePermissionManagementService;
use App\Services\Administration\UserManagementService;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RoleManagementPermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AdministrationPrivilegeTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $administrator;

    private UserModel $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        $this->administrator = $this->user(RoleCode::SystemAdmin);
        $this->manager = $this->user(RoleCode::RegistryManager);
        $this->grant(RoleCode::RegistryManager, PermissionCode::UsersManage);
    }

    public function test_delegated_user_management_does_not_allow_editing_role_permissions(): void
    {
        $this->signIn($this->manager);
        $this->get(route('roles.index'))->assertForbidden();
        $this->putJson(route('roles.permissions.update', $this->manager->role), [
            'permissions' => [PermissionCode::RolesManage->value],
        ])->assertForbidden();
        $this->get(route('users.index'))->assertOk()
            ->assertDontSee(route('roles.index'));
    }

    public function test_legacy_roles_manage_grant_does_not_authorize_a_non_administrator(): void
    {
        $this->grant(RoleCode::RegistryManager, PermissionCode::RolesManage);
        $this->assertFalse($this->manager->can(PermissionCode::RolesManage->value));
        $this->signIn($this->manager);
        $this->get(route('roles.index'))->assertForbidden();
    }

    public function test_roles_manage_cannot_be_assigned_to_an_ordinary_role(): void
    {
        $this->signIn($this->administrator);
        $this->putJson(route('roles.permissions.update', $this->manager->role), [
            'permissions' => [PermissionCode::RolesManage->value],
        ])->assertUnprocessable()->assertJsonValidationErrors('permissions');

        $this->assertTrue($this->manager->can(PermissionCode::UsersManage->value));
        $this->assertFalse($this->manager->can(PermissionCode::RolesManage->value));
    }

    public function test_role_permission_service_rechecks_authorization_without_an_http_request(): void
    {
        $this->expectException(AuthorizationException::class);

        app(RolePermissionManagementService::class)->srv_update(
            $this->manager->role,
            UpdateRolePermissionsDTO::fromValidated(['permissions' => [PermissionCode::AuditView->value]]),
            $this->manager,
        );
    }

    public function test_delegated_manager_cannot_create_an_administrator(): void
    {
        $this->signIn($this->manager);
        $this->postJson(route('users.store'), [
            ...$this->updateData($this->manager, RoleCode::SystemAdmin),
            'username' => 'forged.administrator',
            'email' => 'forged.admin@example.test',
        ])->assertForbidden();

        $this->assertDatabaseMissing('adm_user_models', ['username' => 'forged.administrator']);
    }

    public function test_delegated_manager_cannot_promote_their_own_account(): void
    {
        $this->signIn($this->manager);
        $this->putJson(route('users.update', $this->manager), $this->updateData($this->manager, RoleCode::SystemAdmin))
            ->assertForbidden();

        $this->assertSame(RoleCode::RegistryManager->value, $this->manager->refresh()->role->role_code);
    }

    public function test_delegated_manager_cannot_promote_another_account(): void
    {
        $target = $this->user(RoleCode::SearchAgent);
        $this->signIn($this->manager);
        $this->putJson(route('users.update', $target), $this->updateData($target, RoleCode::SystemAdmin))
            ->assertForbidden();

        $this->assertSame(RoleCode::SearchAgent->value, $target->refresh()->role->role_code);
    }

    public function test_delegated_manager_cannot_open_or_modify_an_administrator_account(): void
    {
        $this->signIn($this->manager);
        $this->get(route('users.edit', $this->administrator))->assertForbidden();
        $originalEmail = $this->administrator->email;
        $this->putJson(route('users.update', $this->administrator), [
            ...$this->updateData($this->administrator, RoleCode::SearchAgent),
            'email' => 'takeover@example.test',
        ])->assertForbidden();

        $this->assertSame($originalEmail, $this->administrator->refresh()->email);
        $this->assertSame(RoleCode::SystemAdmin->value, $this->administrator->role->role_code);
    }

    public function test_delegated_manager_cannot_disable_an_administrator(): void
    {
        $this->signIn($this->manager);
        $this->patchJson(route('users.status.update', $this->administrator), ['is_active' => false])
            ->assertForbidden();

        $this->assertTrue($this->administrator->refresh()->is_active);
    }

    public function test_an_inactive_administrator_is_still_protected(): void
    {
        $target = $this->user(RoleCode::SystemAdmin);
        $target->update(['is_active' => false]);
        $this->signIn($this->manager);
        $this->patchJson(route('users.status.update', $target), ['is_active' => true])->assertForbidden();
        $this->putJson(route('users.update', $target), $this->updateData($target, RoleCode::SearchAgent))
            ->assertForbidden();

        $this->assertFalse($target->refresh()->is_active);
        $this->assertSame(RoleCode::SystemAdmin->value, $target->role->role_code);
    }

    public function test_delegated_manager_cannot_reset_an_administrator_password(): void
    {
        $this->signIn($this->manager);
        $originalHash = $this->administrator->password;
        $this->postJson(route('users.password.reset', $this->administrator), [
            'current_password' => 'Delegate_Password2026#',
        ])->assertForbidden();

        $this->assertSame($originalHash, $this->administrator->refresh()->password);
    }

    public function test_delegated_manager_can_manage_an_ordinary_account(): void
    {
        $target = $this->user(RoleCode::SearchAgent);
        $this->signIn($this->manager);
        $this->get(route('users.create'))->assertOk()
            ->assertDontSee('value="'.$this->role(RoleCode::SystemAdmin)->role_code_unique.'"', false);
        $this->get(route('users.edit', $target))->assertOk();
        $this->put(route('users.update', $target), [
            ...$this->updateData($target, RoleCode::SearchAgent),
            'first_names' => 'Updated',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Updated', $target->refresh()->first_names);

        $this->post(route('users.password.reset', $target), [
            'current_password' => 'Delegate_Password2026#',
        ])->assertRedirect()->assertSessionHas('temporary_password');
        $this->assertTrue(Hash::check(session('temporary_password'), $target->refresh()->password));

        $this->patch(route('users.status.update', $target), ['is_active' => false])->assertRedirect();
        $this->assertFalse($target->refresh()->is_active);
    }

    public function test_system_administrator_can_promote_an_ordinary_account(): void
    {
        $target = $this->user(RoleCode::SearchAgent);
        $this->signIn($this->administrator);
        $this->put(route('users.update', $target), $this->updateData($target, RoleCode::SystemAdmin))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(RoleCode::SystemAdmin->value, $target->refresh()->role->role_code);
    }

    public function test_delegated_manager_can_create_an_ordinary_account(): void
    {
        $this->signIn($this->manager);
        $this->post(route('users.store'), [
            ...$this->updateData($this->manager, RoleCode::SearchAgent),
            'username' => 'delegated.agent',
            'email' => 'delegated.agent@example.test',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('adm_user_models', [
            'username' => 'delegated.agent',
            'role_code_unique' => $this->role(RoleCode::SearchAgent)->role_code_unique,
        ]);
    }

    public function test_focused_seeder_preserves_existing_delegations_and_account_passwords(): void
    {
        $hash = $this->manager->password;
        $count = UserModel::query()->count();
        $permission = PermissionModel::query()->where('permission_code', PermissionCode::MissionsAssign->value)->sole();
        RolePermissionModel::query()->where('role_code_unique', $this->manager->role_code_unique)
            ->where('permission_code_unique', $permission->permission_code_unique)->update(['is_active' => false]);

        $this->seed(RoleManagementPermissionSeeder::class);
        $this->seed(RoleManagementPermissionSeeder::class);

        $this->assertSame($count, UserModel::query()->count());
        $this->assertSame($hash, $this->manager->refresh()->password);
        $this->assertTrue($this->manager->can(PermissionCode::UsersManage->value));
        $this->assertFalse($this->manager->can(PermissionCode::MissionsAssign->value));
        $this->assertTrue($this->administrator->can(PermissionCode::RolesManage->value));
        $this->assertDatabaseCount('adm_permission_models', 27);
    }

    public function test_service_rechecks_actor_role_after_a_concurrent_demotion(): void
    {
        $this->user(RoleCode::SystemAdmin);
        $target = $this->user(RoleCode::SearchAgent);
        UserModel::query()->whereKey($this->administrator->id)->update([
            'role_code_unique' => $this->role(RoleCode::RegistryManager)->role_code_unique,
        ]);
        $this->expectException(AuthorizationException::class);

        app(UserManagementService::class)->srv_update(
            $target,
            UpdateUserDTO::fromValidated($this->updateData($target, RoleCode::SystemAdmin)),
            $this->administrator,
        );
    }

    public function test_role_permission_service_rechecks_a_demoted_actor(): void
    {
        $this->user(RoleCode::SystemAdmin);
        UserModel::query()->whereKey($this->administrator->id)->update([
            'role_code_unique' => $this->role(RoleCode::RegistryManager)->role_code_unique,
        ]);
        $this->expectException(AuthorizationException::class);

        app(RolePermissionManagementService::class)->srv_update(
            $this->role(RoleCode::SearchAgent),
            UpdateRolePermissionsDTO::fromValidated(['permissions' => [PermissionCode::UsersManage->value]]),
            $this->administrator,
        );
    }

    public function test_service_uses_current_target_role_instead_of_a_stale_model(): void
    {
        $target = $this->user(RoleCode::SearchAgent);
        UserModel::query()->whereKey($target->id)->update([
            'role_code_unique' => $this->role(RoleCode::SystemAdmin)->role_code_unique,
        ]);
        $this->expectException(AuthorizationException::class);

        app(UserManagementService::class)->srv_update(
            $target,
            UpdateUserDTO::fromValidated($this->updateData($target, RoleCode::SearchAgent)),
            $this->manager,
        );
    }

    private function user(RoleCode $role): UserModel
    {
        return UserModel::factory()->create([
            'role_code_unique' => $this->role($role)->role_code_unique,
            'username' => 'security.'.Str::lower(Str::random(12)),
            'password' => 'Delegate_Password2026#',
            'must_change_password' => false,
        ]);
    }

    private function role(RoleCode $code): RoleModel
    {
        return RoleModel::query()->where('role_code', $code->value)->sole();
    }

    private function grant(RoleCode $role, PermissionCode $permission): void
    {
        RolePermissionModel::query()->updateOrCreate([
            'role_code_unique' => $this->role($role)->role_code_unique,
            'permission_code_unique' => PermissionModel::query()->where('permission_code', $permission->value)->sole()->permission_code_unique,
        ], ['is_active' => true]);
    }

    /** @return array<string, mixed> */
    private function updateData(UserModel $user, RoleCode $role): array
    {
        return [
            'role_code_unique' => $this->role($role)->role_code_unique,
            'service_code_unique' => $user->service_code_unique,
            'username' => $user->username,
            'last_name' => $user->last_name,
            'first_names' => $user->first_names,
            'email' => $user->email,
            'phone' => $user->phone,
        ];
    }

    private function signIn(UserModel $user): void
    {
        $this->actingAs($user)->withSession(['auth_session_version' => $user->session_version]);
    }
}
