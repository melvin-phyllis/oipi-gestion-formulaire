<?php

namespace Tests\Feature\Authentication;

use App\Enums\Administration\RoleCode;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_temporary_password_forces_user_to_change_it(): void
    {
        $user = $this->createUser(['must_change_password' => true]);

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'CurrentSecret123!',
        ])->assertRedirect(route('password.change'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('password.change'));
        $this->get(route('password.change'))->assertOk();
    }

    public function test_user_can_change_the_temporary_password(): void
    {
        $user = $this->createUser(['must_change_password' => true]);

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'CurrentSecret123!',
        ]);

        $this->post(route('password.change.update'), [
            'current_password' => 'CurrentSecret123!',
            'password' => 'ReplacementSecret456!',
            'password_confirmation' => 'ReplacementSecret456!',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertSame(2, $user->session_version);
        $this->assertTrue(Hash::check('ReplacementSecret456!', $user->password));
        $this->assertSame(2, session('auth_session_version'));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_current_password_must_be_correct(): void
    {
        $user = $this->createUser(['must_change_password' => true]);

        $this->actingAs($user)
            ->withSession(['auth_session_version' => $user->session_version])
            ->post(route('password.change.update'), [
                'current_password' => 'WrongSecret123!',
                'password' => 'ReplacementSecret456!',
                'password_confirmation' => 'ReplacementSecret456!',
            ])->assertSessionHasErrors([
                'current_password' => 'Le mot de passe actuel est incorrect.',
            ]);

        $this->assertTrue(Hash::check('CurrentSecret123!', $user->fresh()->password));
    }

    public function test_stale_session_is_revoked(): void
    {
        $user = $this->createUser();
        $oldSessionVersion = $user->session_version;
        $user->increment('session_version');
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth_session_version' => $oldSessionVersion])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /** @param array<string, mixed> $attributes */
    private function createUser(array $attributes = []): UserModel
    {
        $role = RoleModel::query()
            ->where('role_code', RoleCode::SearchAgent->value)
            ->firstOrFail();

        return UserModel::factory()->create($attributes + [
            'role_code_unique' => $role->role_code_unique,
            'password' => 'CurrentSecret123!',
        ]);
    }
}
