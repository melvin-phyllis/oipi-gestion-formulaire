<?php

namespace Tests\Feature\Administration;

use App\Enums\Administration\RoleCode;
use App\Mail\Administration\InitialAdminCredentialsMail;
use App\Models\Administration\UserModel;
use Database\Seeders\Administration\AdminSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_initial_administrator_and_emails_a_temporary_password(): void
    {
        Mail::fake();
        $this->seed(RoleSeeder::class);

        $this->seed(AdminSeeder::class);

        $admin = UserModel::query()->firstOrFail();

        $this->assertSame('admin', $admin->username);
        $this->assertSame('melvin.akou@oipi.ci', $admin->email);
        $this->assertSame(RoleCode::SystemAdmin->value, $admin->role->role_code);
        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->must_change_password);
        $this->assertSame(1, $admin->session_version);

        Mail::assertSent(
            InitialAdminCredentialsMail::class,
            fn (InitialAdminCredentialsMail $mail): bool => $mail->hasTo($admin->email)
                && $mail->username === 'admin'
                && Hash::check($mail->temporaryPassword, $admin->password),
        );
    }

    public function test_reseeding_does_not_reset_existing_password_or_send_another_email(): void
    {
        Mail::fake();
        $this->seed(RoleSeeder::class);
        $this->seed(AdminSeeder::class);

        $admin = UserModel::query()->firstOrFail();
        $admin->password = 'UserChangedSecret123!';
        $admin->must_change_password = false;
        $admin->save();

        $this->seed(AdminSeeder::class);
        $admin->refresh();

        $this->assertTrue(Hash::check('UserChangedSecret123!', $admin->password));
        $this->assertFalse($admin->must_change_password);
        Mail::assertSent(InitialAdminCredentialsMail::class, 1);
        $this->assertDatabaseCount('adm_user_models', 1);
    }
}
