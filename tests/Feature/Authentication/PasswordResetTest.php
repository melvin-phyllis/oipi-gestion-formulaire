<?php

namespace Tests\Feature\Authentication;

use App\Enums\Administration\RoleCode;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Notifications\Authentication\ResetPasswordNotification;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    public function test_active_user_receives_a_reset_link(): void
    {
        $user = $this->createUser();

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas(
            'success',
            'Si cette adresse correspond à un compte actif, un lien de réinitialisation a été envoyé.',
        );

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_password_notification_mail_renders_properly(): void
    {
        $user = $this->createUser();
        $notification = new ResetPasswordNotification('test-sample-token');

        $html = (string) $notification->toMail($user)->render();

        $this->assertStringContainsString('Réinitialisation de votre mot de passe', $html);
        $this->assertStringContainsString('Bonjour '.$user->first_names.',', $html);
        $this->assertStringContainsString('Réinitialiser mon mot de passe', $html);
        $this->assertStringContainsString('Office Ivoirien de la Propriété Intellectuelle (OIPI)', $html);
        $this->assertStringContainsString('Registre Informatisé de Suivi des Formulaires Manquants (RISFM)', $html);
        $this->assertStringContainsString(route('password.reset', ['token' => 'test-sample-token', 'email' => $user->email]), $html);
    }

    public function test_unknown_and_inactive_accounts_receive_the_same_generic_response(): void
    {
        $inactiveUser = $this->createUser(['is_active' => false]);

        foreach ([$inactiveUser->email, 'unknown@oipi.ci'] as $email) {
            $this->post(route('password.email'), [
                'email' => $email,
            ])->assertSessionHas(
                'success',
                'Si cette adresse correspond à un compte actif, un lien de réinitialisation a été envoyé.',
            );
        }

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_a_valid_token(): void
    {
        $user = $this->createUser(['must_change_password' => true]);
        $token = '';

        $this->post(route('password.email'), ['email' => $user->email]);
        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            },
        );

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'ReplacementSecret456!',
            'password_confirmation' => 'ReplacementSecret456!',
        ])->assertRedirect(route('login'));

        $user->refresh();

        $this->assertTrue(Hash::check('ReplacementSecret456!', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertSame(2, $user->session_version);
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $user = $this->createUser();

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'ReplacementSecret456!',
            'password_confirmation' => 'ReplacementSecret456!',
        ])->assertSessionHasErrors([
            'email' => 'Ce lien de réinitialisation est invalide ou a expiré.',
        ]);

        $this->assertTrue(Hash::check('CurrentSecret123!', $user->fresh()->password));
    }

    public function test_reset_mail_uses_configured_origin_despite_a_forged_request_host(): void
    {
        config()->set('app.url', 'https://risfm.oipi.test');
        $user = $this->createUser();

        $this->post('http://attacker.example/forgot-password', [
            'email' => $user->email,
        ])->assertRedirect();

        Notification::assertSentTo($user, ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($user): bool {
                $mail = $notification->toMail($user);
                $this->assertStringStartsWith('https://risfm.oipi.test/reset-password/', $mail->actionUrl);
                $this->assertStringNotContainsString('attacker.example', $mail->actionUrl);
                parse_str((string) parse_url($mail->actionUrl, PHP_URL_QUERY), $query);
                $this->assertSame($user->email, $query['email']);

                return true;
            },
        );
    }

    public function test_reset_url_preserves_configured_port_and_installation_path_without_changing_other_urls(): void
    {
        config()->set('app.url', 'http://localhost:8000/risfm');
        $user = $this->createUser();
        $urls = app(UrlGenerator::class);
        $urls->setRequest(Request::create('http://attacker.example/forgot-password'));
        $originalLoginUrl = $urls->route('login');

        $mail = (new ResetPasswordNotification('test-token'))->toMail($user);

        $this->assertStringStartsWith('http://localhost:8000/risfm/reset-password/test-token?', $mail->actionUrl);
        $this->assertSame($originalLoginUrl, $urls->route('login'));
    }

    public function test_invalid_configured_origin_does_not_fall_back_to_the_request_host(): void
    {
        config()->set('app.url', '');
        $user = $this->createUser();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_URL');

        (new ResetPasswordNotification('test-token'))->toMail($user);
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
