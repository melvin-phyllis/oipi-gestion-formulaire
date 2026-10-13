<?php

namespace Tests\Feature\Administration;

use App\Enums\Administration\RoleCode;
use App\Events\Administration\UserCreated;
use App\Events\Administration\UserPasswordReset;
use App\Events\Administration\UserStatusChanged;
use App\Events\Administration\UserUpdated;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Organization\ServiceModel;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Database\Seeders\Organization\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserManagementTest extends TestCase
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
        $this->administrator = $this->createUserForRole(
            RoleCode::SystemAdmin,
            [
                'username' => 'system_admin',
                'email' => 'system.admin@example.test',
                'password' => 'Administrator123!',
            ],
        );
    }

    public function test_only_an_authorized_user_can_open_user_management(): void
    {
        $this->actingAsCurrent($this->administrator)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Utilisateurs')
            ->assertSee($this->administrator->email);

        $this->actingAsCurrent($this->administrator)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Ajouter un utilisateur')
            ->assertSee('btn-generate-username')
            ->assertSee('Générer l’identifiant')
            ->assertSee(route('users.username-suggestion'))
            ->assertSee('Un mot de passe temporaire sera généré automatiquement.')
            ->assertDontSee('new_user_password')
            ->assertDontSee('new_user_password_confirmation');

        $this->actingAsCurrent($this->administrator)
            ->get(route('users.edit', $this->administrator))
            ->assertOk()
            ->assertSee('Sécurité du compte')
            ->assertDontSee('btn-generate-username')
            ->assertDontSee('Générer l’identifiant');

        $searchAgent = $this->createUserForRole(RoleCode::SearchAgent);

        $this->actingAsCurrent($searchAgent)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_an_administrator_can_create_a_user_with_a_role_and_service(): void
    {
        Event::fake([UserCreated::class]);
        $role = $this->role(RoleCode::SearchAgent);

        $response = $this->actingAsCurrent($this->administrator)
            ->post(route('users.store'), [
                'role_code_unique' => $role->role_code_unique,
                'service_code_unique' => $this->service->service_code_unique,
                'username' => 'new.agent',
                'last_name' => 'Kouassi',
                'first_names' => 'Awa',
                'email' => 'awa.kouassi@example.test',
                'phone' => '0700000000',
            ]);

        $user = UserModel::query()
            ->where('username', 'new.agent')
            ->firstOrFail();

        $response
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHas('temporary_password');
        $temporaryPassword = session('temporary_password');

        $this->assertSame($role->role_code_unique, $user->role_code_unique);
        $this->assertSame($this->service->service_code_unique, $user->service_code_unique);
        $this->assertSame($this->administrator->user_code_unique, $user->created_by_code_unique);
        $this->assertTrue($user->must_change_password);
        $this->assertIsString($temporaryPassword);
        $this->assertSame(20, strlen($temporaryPassword));
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));

        $this->actingAsCurrent($this->administrator)
            ->withSession(['temporary_password' => $temporaryPassword])
            ->get(route('users.edit', $user))
            ->assertOk()
            ->assertSee('Nouveau mot de passe temporaire généré !')
            ->assertSee('temporary-password-container')
            ->assertSee($temporaryPassword);

        Event::assertDispatched(UserCreated::class);
    }

    public function test_creation_generates_a_normalized_unique_username_when_it_is_omitted(): void
    {
        $role = $this->role(RoleCode::SearchAgent);
        UserModel::factory()->create([
            'username' => 'jean-pierre.n-guessan',
            'role_code_unique' => $role->role_code_unique,
            'service_code_unique' => $this->service->service_code_unique,
        ]);

        $response = $this->actingAsCurrent($this->administrator)
            ->post(route('users.store'), [
                'role_code_unique' => $role->role_code_unique,
                'service_code_unique' => $this->service->service_code_unique,
                'last_name' => 'N’GUESSAN KOUASSI',
                'first_names' => 'Jean-Pierre Alain',
                'email' => 'jean.nguessan@example.test',
                'phone' => '',
            ]);

        $user = UserModel::query()
            ->where('email', 'jean.nguessan@example.test')
            ->firstOrFail();

        $response->assertRedirect(route('users.edit', $user));
        $this->assertSame('jean-pierre.n-guessan2', $user->username);
    }

    public function test_authorized_username_suggestion_endpoint_returns_the_next_available_identifier(): void
    {
        UserModel::factory()->create([
            'username' => 'awa.kouassi',
            'role_code_unique' => $this->role(RoleCode::SearchAgent)->role_code_unique,
            'service_code_unique' => $this->service->service_code_unique,
        ]);

        $this->actingAsCurrent($this->administrator)
            ->postJson(route('users.username-suggestion'), [
                'last_name' => 'KOUASSI',
                'first_names' => 'Awa Marie',
            ])
            ->assertOk()
            ->assertExactJson(['username' => 'awa.kouassi2']);

        $unauthorizedUser = $this->createUserForRole(RoleCode::SearchAgent);

        $this->actingAsCurrent($unauthorizedUser)
            ->postJson(route('users.username-suggestion'), [
                'last_name' => 'Yao',
                'first_names' => 'Mireille',
            ])
            ->assertForbidden();
    }

    public function test_an_administrator_can_update_a_user(): void
    {
        Event::fake([UserUpdated::class]);
        $user = $this->createUserForRole(RoleCode::SearchAgent);
        $newRole = $this->role(RoleCode::RegistryManager);
        $newService = ServiceModel::query()
            ->where('service_code', 'SDES')
            ->firstOrFail();

        $this->actingAsCurrent($this->administrator)
            ->put(route('users.update', $user), [
                'role_code_unique' => $newRole->role_code_unique,
                'service_code_unique' => $newService->service_code_unique,
                'username' => 'updated.agent',
                'last_name' => 'Yao',
                'first_names' => 'Mireille',
                'email' => 'mireille.yao@example.test',
                'phone' => '',
            ])
            ->assertRedirect(route('users.edit', $user));

        $user->refresh();

        $this->assertSame('updated.agent', $user->username);
        $this->assertSame($newRole->role_code_unique, $user->role_code_unique);
        $this->assertSame($newService->service_code_unique, $user->service_code_unique);
        $this->assertNull($user->phone);
        Event::assertDispatched(UserUpdated::class);
    }

    public function test_deactivating_a_user_invalidates_existing_sessions(): void
    {
        Event::fake([UserStatusChanged::class]);
        $user = $this->createUserForRole(RoleCode::SearchAgent);
        $initialSessionVersion = $user->session_version;

        $this->actingAsCurrent($this->administrator)
            ->patch(route('users.status.update', $user), ['is_active' => false])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertFalse($user->is_active);
        $this->assertSame($initialSessionVersion + 1, $user->session_version);
        Event::assertDispatched(UserStatusChanged::class);
    }

    public function test_an_administrator_cannot_deactivate_their_own_account(): void
    {
        $this->actingAsCurrent($this->administrator)
            ->patch(route('users.status.update', $this->administrator), ['is_active' => false])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($this->administrator->fresh()->is_active);
    }

    public function test_the_last_active_system_administrator_cannot_be_demoted(): void
    {
        $newRole = $this->role(RoleCode::RegistryManager);

        $this->actingAsCurrent($this->administrator)
            ->put(route('users.update', $this->administrator), [
                'role_code_unique' => $newRole->role_code_unique,
                'service_code_unique' => $this->service->service_code_unique,
                'username' => $this->administrator->username,
                'last_name' => $this->administrator->last_name,
                'first_names' => $this->administrator->first_names,
                'email' => $this->administrator->email,
                'phone' => $this->administrator->phone,
            ])
            ->assertSessionHasErrors('role_code_unique');

        $this->assertSame(
            RoleCode::SystemAdmin->value,
            $this->administrator->fresh()->role->role_code,
        );
    }

    public function test_password_reset_requires_the_administrators_current_password(): void
    {
        Event::fake([UserPasswordReset::class]);
        $user = $this->createUserForRole(RoleCode::SearchAgent, [
            'password' => 'OldUserPassword123!',
            'must_change_password' => false,
        ]);
        $initialSessionVersion = $user->session_version;

        $this->actingAsCurrent($this->administrator)
            ->post(route('users.password.reset', $user), [
                'current_password' => 'WrongAdministratorPassword!',
            ])
            ->assertSessionHasErrors('current_password');

        $response = $this->actingAsCurrent($this->administrator)
            ->post(route('users.password.reset', $user), [
                'current_password' => 'Administrator123!',
            ]);

        $response
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHas('temporary_password');

        $temporaryPassword = session('temporary_password');
        $user->refresh();

        $this->assertIsString($temporaryPassword);
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertSame($initialSessionVersion + 1, $user->session_version);
        Event::assertDispatched(UserPasswordReset::class);
    }

    /** @param array<string, mixed> $attributes */
    private function createUserForRole(
        RoleCode $roleCode,
        array $attributes = [],
    ): UserModel {
        return UserModel::factory()->create($attributes + [
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
