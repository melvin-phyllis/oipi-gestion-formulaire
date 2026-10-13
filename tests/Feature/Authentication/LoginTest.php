<?php

namespace Tests\Feature\Authentication;

use App\Enums\Administration\RoleCode;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_can_view_login_but_not_dashboard(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_active_user_can_login_with_username_and_logout(): void
    {
        $user = $this->createSearchAgent();

        $response = $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'CorrectSecret123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->get(route('dashboard'))->assertOk();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_active_user_can_login_with_email(): void
    {
        $user = $this->createSearchAgent();

        $this->post(route('login.store'), [
            'login' => $user->email,
            'password' => 'CorrectSecret123!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_and_wrong_password_receive_the_same_error(): void
    {
        $inactiveUser = $this->createSearchAgent(['is_active' => false]);
        $activeUser = $this->createSearchAgent();

        $this->from(route('login'))->post(route('login.store'), [
            'login' => $inactiveUser->username,
            'password' => 'CorrectSecret123!',
        ])->assertSessionHasErrors([
            'login' => 'Identifiant ou mot de passe incorrect.',
        ]);

        $this->from(route('login'))->post(route('login.store'), [
            'login' => $activeUser->username,
            'password' => 'WrongSecret123!',
        ])->assertSessionHasErrors([
            'login' => 'Identifiant ou mot de passe incorrect.',
        ]);

        $this->assertGuest();
    }

    public function test_user_without_dashboard_permission_is_forbidden(): void
    {
        $role = RoleModel::factory()->create();
        $user = UserModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
        ]);

        $this->actingAs($user)
            ->withSession(['auth_session_version' => $user->session_version])
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = $this->createSearchAgent();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.store'), [
                'login' => $user->username,
                'password' => 'WrongSecret123!',
            ])->assertSessionHasErrors('login');
        }

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'WrongSecret123!',
        ])->assertTooManyRequests();
    }

    /** @param array<string, mixed> $attributes */
    private function createSearchAgent(array $attributes = []): UserModel
    {
        $role = RoleModel::query()
            ->where('role_code', RoleCode::SearchAgent->value)
            ->firstOrFail();

        return UserModel::factory()->create($attributes + [
            'role_code_unique' => $role->role_code_unique,
            'password' => 'CorrectSecret123!',
        ]);
    }
}
