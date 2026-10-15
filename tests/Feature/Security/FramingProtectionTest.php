<?php

namespace Tests\Feature\Security;

use App\Contracts\Backup\DatabaseProcessInterface;
use App\Enums\Administration\RoleCode;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class FramingProtectionTest extends TestCase
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

    protected function tearDown(): void
    {
        Request::setTrustedHosts([]);

        parent::tearDown();
    }

    public function test_guest_authentication_pages_and_head_responses_refuse_framing(): void
    {
        $this->assertProtected($this->get(route('login'))->assertOk());
        $this->assertProtected($this->get(route('password.request'))->assertOk());
        $this->assertProtected($this->head(route('login'))->assertOk());
    }

    public function test_authenticated_pages_refuse_framing_without_affecting_permissions(): void
    {
        $this->signIn(RoleCode::SystemAdmin);

        $this->assertProtected($this->get(route('dashboard'))->assertOk());
        $this->assertProtected($this->get(route('audit.index'))->assertOk());
        $this->assertProtected($this->get(route('backups.index'))->assertOk());
    }

    public function test_authentication_redirects_also_refuse_framing(): void
    {
        $this->assertProtected($this->get(route('audit.index'))->assertRedirect(route('login')));
    }

    public function test_permission_errors_refuse_framing_for_html_and_json(): void
    {
        $this->signIn(RoleCode::ReadOnly);

        $this->assertProtected($this->get(route('audit.index'))->assertForbidden());
        $this->assertProtected($this->getJson(route('audit.index'))->assertForbidden());
    }

    public function test_missing_pages_refuse_framing_for_html_and_json(): void
    {
        $this->assertProtected($this->get('/__framing_test_missing')->assertNotFound());
        $this->assertProtected($this->getJson('/__framing_test_missing')->assertNotFound());
    }

    public function test_validation_errors_keep_json_contract_and_refuse_framing(): void
    {
        $this->assertProtected($this->postJson(route('login.store'), [])
            ->assertUnprocessable()->assertJsonValidationErrors(['login', 'password']));
    }

    public function test_rate_limit_responses_keep_retry_after_and_refuse_framing(): void
    {
        $data = ['login' => 'unknown-framing-user', 'password' => 'WrongSecret123!'];
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson(route('login.store'), $data)->assertUnprocessable();
        }

        $this->assertProtected($this->postJson(route('login.store'), $data)
            ->assertTooManyRequests()->assertHeader('Retry-After')->assertJsonStructure(['message', 'retry_after']));
    }

    public function test_host_rejection_in_global_middleware_still_refuses_framing(): void
    {
        config()->set('app.url', 'https://risfm.oipi.test');
        $this->app->instance('env', 'production');

        $this->assertProtected($this->get('https://attacker.example/login')->assertStatus(400));
    }

    public function test_maintenance_response_in_global_middleware_refuses_framing(): void
    {
        $this->mock(PreventRequestsDuringMaintenance::class, function ($mock): void {
            $mock->shouldReceive('handle')->once()
                ->andThrow(new ServiceUnavailableHttpException(42, 'Maintenance test.'));
        });

        $this->assertProtected($this->get('/login')->assertStatus(503)->assertHeader('Retry-After', '42'));
    }

    public function test_unexpected_server_errors_refuse_framing(): void
    {
        config()->set('app.debug', false);
        Route::get('/__framing_test_error', function (): never {
            throw new RuntimeException('Framing protection test exception.');
        });

        $this->assertProtected($this->get('/__framing_test_error')->assertStatus(500));
    }

    public function test_streamed_downloads_keep_content_type_and_refuse_framing(): void
    {
        Route::get('/__framing_test_download', fn () => response()->streamDownload(
            function (): void {
                echo 'Test-only download.';
            },
            'test.txt',
            ['Content-Type' => 'text/plain'],
        ));

        $this->assertProtected($this->get('/__framing_test_download')->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')->assertDownload('test.txt'));
    }

    public function test_existing_csp_policies_are_preserved_and_framing_is_strictly_denied(): void
    {
        $existingPolicies = ["default-src 'self'; script-src 'self'", "frame-ancestors *; connect-src 'self' wss://example.test"];
        Route::get('/__framing_test_policies', fn () => response('Test.', 200, [
            'Content-Security-Policy' => $existingPolicies,
            'X-Frame-Options' => 'SAMEORIGIN',
        ]));

        $response = $this->get('/__framing_test_policies')->assertOk()->assertHeader('X-Frame-Options', 'DENY');
        $policies = $response->headers->all('Content-Security-Policy');
        $this->assertSame([...$existingPolicies, "frame-ancestors 'none'"], array_slice($policies, 1));
        $this->assertStringContainsString("frame-ancestors 'none'", $policies[0]);
    }

    public function test_identical_existing_framing_policy_is_not_duplicated(): void
    {
        Route::get('/__framing_test_policy', fn () => response('Test.', 200, [
            'Content-Security-Policy' => "frame-ancestors 'none'",
        ]));

        $response = $this->get('/__framing_test_policy')->assertOk();
        $this->assertProtected($response);
        $this->assertCount(1, array_filter($response->headers->all('Content-Security-Policy'), fn (string $policy): bool => $policy === "frame-ancestors 'none'"));
    }

    private function signIn(RoleCode $roleCode): void
    {
        $user = UserModel::factory()->create([
            'role_code_unique' => RoleModel::query()->where('role_code', $roleCode->value)->sole()->role_code_unique,
            'service_code_unique' => null,
        ]);
        $this->actingAs($user)->withSession(['auth_session_version' => $user->session_version]);
    }

    private function assertProtected(TestResponse $response): void
    {
        $response->assertHeader('X-Frame-Options', 'DENY');
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }
}
