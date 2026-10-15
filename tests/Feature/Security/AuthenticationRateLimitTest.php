<?php

namespace Tests\Feature\Security;

use App\Contracts\Backup\DatabaseProcessInterface;
use App\Enums\Administration\RoleCode;
use App\Enums\Backup\BackupType;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Backup\DatabaseBackupModel;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class AuthenticationRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        $this->mock(DatabaseProcessInterface::class, function ($mock): void {
            $mock->shouldNotReceive('process_dump');
            $mock->shouldNotReceive('process_restore');
        });
    }

    public function test_login_ip_limit_cannot_be_bypassed_by_rotating_identifiers_or_forwarded_headers(): void
    {
        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->withHeader('X-Forwarded-For', "198.51.100.{$attempt}")
                ->postJson(route('login.store'), [
                    'login' => "unknown-{$attempt}",
                    'password' => 'WrongSecret123!',
                ])->assertUnprocessable()->assertJsonValidationErrors('login');
        }

        $this->assertLimited($this->postJson(route('login.store'), [
            'login' => 'another-identifier',
            'password' => 'WrongSecret123!',
        ]));
        $this->assertGuest();
    }

    public function test_login_account_limit_is_shared_by_username_email_and_different_ips(): void
    {
        $user = $this->user();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->ip($attempt)->postJson(route('login.store'), [
                'login' => $attempt % 2 === 0 ? $user->email : $user->username,
                'password' => 'WrongSecret123!',
            ])->assertUnprocessable()->assertJsonValidationErrors('login');
        }

        $this->assertLimited($this->ip(6)->postJson(route('login.store'), [
            'login' => $user->email,
            'password' => 'CorrectSecret123!',
        ]));
        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_unknown_identifier_limit_normalizes_case_spaces_and_accents(): void
    {
        foreach ([' unknown ', 'UNKNOWN', 'Unknown', 'unknówn', 'unknown'] as $index => $identifier) {
            $this->ip($index + 1)->postJson(route('login.store'), [
                'login' => $identifier,
                'password' => 'WrongSecret123!',
            ])->assertUnprocessable();
        }

        $this->assertLimited($this->ip(6)->postJson(route('login.store'), [
            'login' => 'unknown',
            'password' => 'WrongSecret123!',
        ]));
    }

    public function test_another_account_on_the_same_ip_can_still_sign_in(): void
    {
        $blockedUser = $this->user();
        $otherUser = $this->user();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson(route('login.store'), [
                'login' => $blockedUser->username,
                'password' => 'WrongSecret123!',
            ])->assertUnprocessable();
        }

        $this->post(route('login.store'), [
            'login' => $otherUser->username,
            'password' => 'CorrectSecret123!',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($otherUser);
    }

    public function test_login_is_available_after_the_wait_and_get_pages_are_not_blocked(): void
    {
        $user = $this->user();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson(route('login.store'), [
                'login' => $user->username,
                'password' => 'WrongSecret123!',
            ])->assertUnprocessable();
        }

        $this->assertLimited($this->postJson(route('login.store'), [
            'login' => $user->username,
            'password' => 'CorrectSecret123!',
        ]));
        $this->get(route('login'))->assertOk();
        $this->travel(61)->seconds();
        $this->post(route('login.store'), [
            'login' => $user->email,
            'password' => 'CorrectSecret123!',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_recovery_ip_limit_cannot_be_bypassed_by_rotating_emails(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->post(route('password.email'), ['email' => "unknown{$attempt}@example.test"])
                ->assertRedirect()->assertSessionHas('success');
        }

        $this->assertLimited($this->postJson(route('password.email'), ['email' => 'another@example.test']));
    }

    public function test_password_recovery_account_limit_survives_ip_changes(): void
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->ip($attempt)->post(route('password.email'), ['email' => 'unknown@example.test'])
                ->assertRedirect()->assertSessionHas('success');
        }

        $this->assertLimited($this->ip(4)->postJson(route('password.email'), ['email' => 'unknown@example.test']));
    }

    public function test_public_reset_and_recovery_share_the_same_account_counter(): void
    {
        $user = $this->user();
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->ip($attempt)->postJson(route('password.update'), [
                'email' => $user->email,
                'token' => 'invalid-token',
                'password' => 'ReplacementSecret456!',
                'password_confirmation' => 'ReplacementSecret456!',
            ])->assertUnprocessable()->assertJsonValidationErrors('email');
        }

        $this->assertLimited($this->ip(3)->postJson(route('password.update'), [
            'email' => $user->email,
            'token' => 'another-invalid-token',
            'password' => 'ReplacementSecret456!',
            'password_confirmation' => 'ReplacementSecret456!',
        ]));
        $this->assertTrue(Hash::check('CorrectSecret123!', $user->fresh()->password));
    }

    public function test_password_change_attempts_are_limited_even_from_different_ips(): void
    {
        $user = $this->user();
        $this->signIn($user);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->ip($attempt)->postJson(route('password.change.update'), $this->passwordChangeData())
                ->assertUnprocessable()->assertJsonValidationErrors('current_password');
        }

        $this->assertLimited($this->ip(6)->postJson(route('password.change.update'), $this->passwordChangeData()));
        $this->assertTrue(Hash::check('CorrectSecret123!', $user->fresh()->password));
        $this->assertSame(1, $user->fresh()->session_version);
    }

    public function test_restore_attempts_are_limited_without_running_database_operations(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        $backup = $this->backup();
        $this->signIn($administrator);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->ip($attempt)->postJson(route('backups.restore', $backup), $this->restoreData())
                ->assertUnprocessable()->assertJsonValidationErrors('current_password');
        }

        $this->assertLimited($this->ip(6)->postJson(route('backups.restore', $this->backup()), $this->restoreData()));
        $this->assertDatabaseCount('bkp_backup_models', 2);
    }

    public function test_administrative_password_attempts_share_the_actor_counter_across_targets(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        $this->signIn($administrator);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->ip($attempt)->postJson(route('users.password.reset', $this->user()), [
                'current_password' => 'WrongSecret123!',
            ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        }

        $target = $this->user();
        $this->assertLimited($this->ip(6)->postJson(route('users.password.reset', $target), [
            'current_password' => 'CorrectSecret123!',
        ]));
        $this->assertSame(1, $target->fresh()->session_version);
        $this->assertFalse($target->fresh()->must_change_password);
    }

    public function test_current_password_counter_is_shared_across_all_sensitive_actions(): void
    {
        $administrator = $this->user(RoleCode::SystemAdmin);
        $this->signIn($administrator);
        $backup = $this->backup();

        $this->postJson(route('backups.restore', $backup), $this->restoreData())->assertUnprocessable();
        $this->postJson(route('password.change.update'), $this->passwordChangeData())->assertUnprocessable();
        $this->postJson(route('users.password.reset', $this->user()), [
            'current_password' => 'WrongSecret123!',
        ])->assertUnprocessable();
        $this->patchJson(route('profile.update'), $this->profileData($administrator))->assertUnprocessable();
        $this->postJson(route('backups.restore', $backup), $this->restoreData())->assertUnprocessable();

        $this->assertLimited($this->ip(2)->postJson(route('password.change.update'), $this->passwordChangeData()));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_sensitive_ip_limit_cannot_be_bypassed_by_changing_authenticated_accounts(): void
    {
        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->signIn($this->user());
            $this->postJson(route('password.change.update'), $this->passwordChangeData())
                ->assertUnprocessable()->assertJsonValidationErrors('current_password');
        }

        $this->signIn($this->user());
        $this->assertLimited($this->postJson(route('password.change.update'), $this->passwordChangeData()));
    }

    public function test_sensitive_account_limit_does_not_block_another_actor_on_the_same_ip(): void
    {
        $this->signIn($this->user());
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson(route('password.change.update'), $this->passwordChangeData())->assertUnprocessable();
        }

        $this->assertLimited($this->postJson(route('password.change.update'), $this->passwordChangeData()));
        $this->signIn($this->user());
        $this->postJson(route('password.change.update'), $this->passwordChangeData())
            ->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }

    public function test_profile_email_changes_are_limited_but_ordinary_profile_updates_are_not_sensitive(): void
    {
        $user = $this->user();
        $this->signIn($user);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->ip($attempt)->patchJson(route('profile.update'), $this->profileData($user))
                ->assertUnprocessable()->assertJsonValidationErrors('current_password');
        }

        $this->assertLimited($this->ip(6)->patchJson(route('profile.update'), $this->profileData($user)));
        $this->patch(route('profile.update'), [
            ...$this->profileData($user),
            'email' => $user->email,
            'current_password' => null,
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_legacy_uppercase_email_cannot_bypass_sensitive_profile_limits(): void
    {
        $user = $this->user();
        $user->update(['email' => 'Legacy@Example.test']);
        $this->signIn($user);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->ip($attempt)->patchJson(route('profile.update'), [
                ...$this->profileData($user),
                'email' => 'legacy@example.test',
            ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        }

        $this->assertLimited($this->ip(6)->patchJson(route('profile.update'), [
            ...$this->profileData($user),
            'email' => 'legacy@example.test',
        ]));
    }

    public function test_sensitive_actions_can_be_retried_after_the_wait(): void
    {
        $user = $this->user();
        $this->signIn($user);
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson(route('password.change.update'), $this->passwordChangeData())->assertUnprocessable();
        }

        $this->assertLimited($this->postJson(route('password.change.update'), $this->passwordChangeData()));
        $this->travel(61)->seconds();
        $this->post(route('password.change.update'), [
            ...$this->passwordChangeData(),
            'current_password' => 'CorrectSecret123!',
        ])->assertRedirect(route('dashboard'));
        $this->assertTrue(Hash::check('ReplacementSecret456!', $user->fresh()->password));
    }

    public function test_sensitive_routes_all_declare_the_shared_limiter(): void
    {
        foreach (['password.change.update', 'profile.update', 'users.password.reset', 'backups.restore'] as $name) {
            $this->assertContains('throttle:sensitive-actions', Route::getRoutes()->getByName($name)->gatherMiddleware());
        }
    }

    private function user(RoleCode $roleCode = RoleCode::SearchAgent): UserModel
    {
        return UserModel::factory()->create([
            'role_code_unique' => RoleModel::query()->where('role_code', $roleCode->value)->sole()->role_code_unique,
            'password' => 'CorrectSecret123!',
        ]);
    }

    private function backup(): DatabaseBackupModel
    {
        $filename = fake()->uuid().'.sql.gz';

        return DatabaseBackupModel::query()->create([
            'backup_type' => BackupType::Manual,
            'disk' => 'local',
            'file_path' => 'backups/database/'.$filename,
            'file_name' => $filename,
            'size_bytes' => 1,
            'sha256' => str_repeat('a', 64),
            'database_name' => 'test_only',
        ]);
    }

    private function signIn(UserModel $user): void
    {
        $this->actingAs($user)->withSession(['auth_session_version' => $user->session_version]);
    }

    private function ip(int $index): self
    {
        return $this->withServerVariables(['REMOTE_ADDR' => "192.0.2.{$index}"]);
    }

    /** @return array<string, string> */
    private function passwordChangeData(): array
    {
        return [
            'current_password' => 'WrongSecret123!',
            'password' => 'ReplacementSecret456!',
            'password_confirmation' => 'ReplacementSecret456!',
        ];
    }

    /** @return array<string, string> */
    private function restoreData(): array
    {
        return [
            'confirmation_phrase' => 'RESTAURER OIPI',
            'current_password' => 'WrongSecret123!',
        ];
    }

    /** @return array<string, string> */
    private function profileData(UserModel $user): array
    {
        return [
            'last_name' => $user->last_name,
            'first_names' => $user->first_names,
            'email' => 'changed@example.test',
            'current_password' => 'WrongSecret123!',
        ];
    }

    private function assertLimited(TestResponse $response): void
    {
        $response->assertTooManyRequests()->assertHeader('Retry-After')
            ->assertJsonStructure(['message', 'retry_after'])
            ->assertJsonPath('retry_after', (int) $response->headers->get('Retry-After'))
            ->assertJsonMissingValidationErrors()
            ->assertDontSee('WrongSecret123!');

        $this->assertStringContainsString('Trop de tentatives', $response->json('message'));
        $this->assertGreaterThan(0, $response->json('retry_after'));
        $this->assertLessThanOrEqual(60, $response->json('retry_after'));
    }
}
