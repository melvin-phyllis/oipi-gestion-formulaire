<?php

namespace Tests\Feature\Administration;

use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Enums\Audit\AuditAction;
use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\RolePermissionModel;
use App\Models\Administration\UserModel;
use App\Models\Organization\ServiceModel;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Database\Seeders\Organization\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ServiceSeeder::class);

        $role = RoleModel::query()
            ->where('role_code', RoleCode::SearchAgent->value)
            ->firstOrFail();
        $service = ServiceModel::query()->where('service_code', 'SDSID')->firstOrFail();

        $this->user = UserModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
            'service_code_unique' => $service->service_code_unique,
            'username' => 'agent.profile',
            'last_name' => 'KOUASSI',
            'first_names' => 'Awa',
            'email' => 'awa.kouassi@example.test',
            'phone' => '0700000000',
            'password' => 'CurrentSecret123!',
            'must_change_password' => false,
        ]);
    }

    public function test_profile_update_route_is_explicitly_protected(): void
    {
        $route = Route::getRoutes()->getByName('profile.update');

        $this->assertNotNull($route);
        $this->assertContains('PATCH', $route->methods());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('session.current', $route->gatherMiddleware());
        $this->assertContains('password.changed', $route->gatherMiddleware());
        $this->assertContains('permission:profile_update', $route->gatherMiddleware());
        $this->assertContains('throttle:profile-updates', $route->gatherMiddleware());
    }

    public function test_user_can_update_personal_information_without_changing_account_authority(): void
    {
        $originalRole = $this->user->role_code_unique;
        $originalService = $this->user->service_code_unique;
        $originalUsername = $this->user->username;

        $this->actingAsCurrent($this->user)
            ->from('/dashboard')
            ->patch(route('profile.update'), [
                'last_name' => '  YAO  ',
                'first_names' => '  Mireille Awa  ',
                'email' => $this->user->email,
                'phone' => '  0500000000  ',
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Votre profil a été mis à jour.');

        $updated = $this->user->fresh();

        $this->assertSame('YAO', $updated->last_name);
        $this->assertSame('Mireille Awa', $updated->first_names);
        $this->assertSame('0500000000', $updated->phone);
        $this->assertSame($originalRole, $updated->role_code_unique);
        $this->assertSame($originalService, $updated->service_code_unique);
        $this->assertSame($originalUsername, $updated->username);
        $this->assertDatabaseHas('aud_audit_log_models', [
            'action_code' => AuditAction::ProfileUpdated->value,
            'actor_user_code_unique' => $updated->user_code_unique,
            'subject_uuid' => $updated->uuid,
        ]);
    }

    public function test_changing_email_requires_the_current_password(): void
    {
        $payload = [
            'last_name' => $this->user->last_name,
            'first_names' => $this->user->first_names,
            'email' => 'new.email@example.test',
            'phone' => $this->user->phone,
        ];

        $this->actingAsCurrent($this->user)
            ->patch(route('profile.update'), $payload)
            ->assertSessionHasErrors('current_password');

        $this->actingAsCurrent($this->user)
            ->patch(route('profile.update'), $payload + [
                'current_password' => 'WrongSecret123!',
            ])
            ->assertSessionHasErrors([
                'current_password' => 'Le mot de passe actuel est incorrect.',
            ]);

        $this->assertSame('awa.kouassi@example.test', $this->user->fresh()->email);

        $this->actingAsCurrent($this->user)
            ->patch(route('profile.update'), $payload + [
                'current_password' => 'CurrentSecret123!',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('new.email@example.test', $this->user->fresh()->email);
        $this->assertTrue(Hash::check('CurrentSecret123!', $this->user->fresh()->password));
    }

    public function test_email_must_remain_unique(): void
    {
        $otherUser = UserModel::factory()->create(['email' => 'existing@example.test']);

        $this->actingAsCurrent($this->user)
            ->patch(route('profile.update'), [
                'last_name' => $this->user->last_name,
                'first_names' => $this->user->first_names,
                'email' => $otherUser->email,
                'phone' => $this->user->phone,
                'current_password' => 'CurrentSecret123!',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('awa.kouassi@example.test', $this->user->fresh()->email);
    }

    public function test_profile_endpoint_rejects_administrative_and_security_fields(): void
    {
        $otherRole = RoleModel::query()
            ->where('role_code', RoleCode::SystemAdmin->value)
            ->firstOrFail();

        $this->actingAsCurrent($this->user)
            ->patch(route('profile.update'), [
                'last_name' => $this->user->last_name,
                'first_names' => $this->user->first_names,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'username' => 'administrator',
                'role_code_unique' => $otherRole->role_code_unique,
                'is_active' => false,
                'password' => 'InjectedPassword123!',
            ])
            ->assertSessionHasErrors([
                'username',
                'role_code_unique',
                'is_active',
                'password',
            ]);

        $this->user->refresh();
        $this->assertSame('agent.profile', $this->user->username);
        $this->assertSame(RoleCode::SearchAgent->value, $this->user->role->role_code);
        $this->assertTrue($this->user->is_active);
        $this->assertTrue(Hash::check('CurrentSecret123!', $this->user->password));
    }

    public function test_user_without_profile_permission_is_forbidden(): void
    {
        $permission = PermissionModel::query()
            ->where('permission_code', PermissionCode::ProfileUpdate->value)
            ->firstOrFail();
        RolePermissionModel::query()
            ->where('role_code_unique', $this->user->role_code_unique)
            ->where('permission_code_unique', $permission->permission_code_unique)
            ->update(['is_active' => false]);

        $this->actingAsCurrent($this->user)
            ->patch(route('profile.update'), [
                'last_name' => 'INTERDIT',
                'first_names' => $this->user->first_names,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ])
            ->assertForbidden();

        $this->assertSame('KOUASSI', $this->user->fresh()->last_name);
    }

    public function test_user_can_view_profile_edit_page(): void
    {
        $this->actingAsCurrent($this->user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Mon profil utilisateur')
            ->assertSee($this->user->user_code_unique)
            ->assertSee($this->user->username)
            ->assertSee($this->user->email)
            ->assertSee($this->user->phone)
            ->assertSee(route('profile.update'))
            ->assertSee(route('password.change'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));

        $this->patch(route('profile.update'), [
            'last_name' => 'KOUASSI',
            'first_names' => 'Awa',
            'email' => 'awa.kouassi@example.test',
        ])->assertRedirect(route('login'));
    }

    private function actingAsCurrent(UserModel $user): static
    {
        return $this->actingAs($user)->withSession([
            'auth_session_version' => $user->session_version,
        ]);
    }
}
