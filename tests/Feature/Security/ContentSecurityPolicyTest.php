<?php

namespace Tests\Feature\Security;

use App\Contracts\Backup\DatabaseProcessInterface;
use App\Enums\Administration\RoleCode;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ?string $testHotFile = null;

    private string $originalHotFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalHotFile = Vite::hotFile();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        $this->mock(DatabaseProcessInterface::class, function ($mock): void {
            $mock->shouldNotReceive('process_dump');
            $mock->shouldNotReceive('process_restore');
        });
    }

    protected function tearDown(): void
    {
        Vite::useHotFile($this->originalHotFile);
        if ($this->testHotFile !== null) {
            unlink($this->testHotFile);
        }
        Request::setTrustedHosts([]);
        parent::tearDown();
    }

    public function test_policy_covers_all_resources_and_blocks_unsafe_javascript(): void
    {
        $response = $this->get(route('login'))->assertOk();
        $directives = $this->directives($response);
        $this->assertSame("'self'", $directives['default-src']);
        foreach (['base-uri', 'object-src', 'frame-src', 'frame-ancestors', 'script-src-attr', 'worker-src', 'media-src'] as $name) {
            $this->assertSame("'none'", $directives[$name], $name);
        }
        $this->assertSame("'self'", $directives['form-action']);
        $this->assertStringContainsString("'strict-dynamic'", $directives['script-src']);
        $this->assertStringNotContainsString('unsafe-inline', $directives['script-src']);
        $this->assertStringNotContainsString('unsafe-eval', $directives['script-src']);
        $this->assertSame("'self' data: blob:", $directives['img-src']);
        $this->assertSame("'self' data:", $directives['font-src']);
        $this->assertSame("'unsafe-inline'", $directives['style-src-attr']);
        $this->assertStringNotContainsString('unsafe-inline', $directives['style-src-elem']);
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_nonce_is_fresh_per_response_and_matches_vite_and_inline_tags(): void
    {
        $first = $this->get(route('login'))->assertOk();
        $second = $this->get(route('login'))->assertOk();
        $this->assertNotSame($this->nonce($first), $this->nonce($second));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]{32}$/', $this->nonce($first));
        $this->assertNoncedTags($first);
        $this->assertNoncedTags($second);
        $this->assertStringContainsString('no-store', $first->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $first->headers->get('Cache-Control'));
    }

    public function test_operational_pages_have_nonced_scripts_and_no_inline_event_attributes(): void
    {
        $administrator = UserModel::factory()->create([
            'role_code_unique' => RoleModel::query()->where('role_code', RoleCode::SystemAdmin->value)->sole()->role_code_unique,
            'service_code_unique' => null,
        ]);
        $this->actingAs($administrator)->withSession(['auth_session_version' => $administrator->session_version]);

        foreach (['/dashboard', '/registry/forms', '/registry/forms/create', '/missions', '/users/create', '/users/'.$administrator->uuid.'/edit', '/roles', '/organization', '/registry/settings', '/notifications', '/analytics', '/audit', '/backups', '/profile'] as $path) {
            $this->assertNoncedTags($this->get($path)->assertOk());
        }
    }

    public function test_error_pages_keep_nonced_scripts_and_styles(): void
    {
        $this->assertNoncedTags($this->get('/__csp_unknown')->assertNotFound());
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.store'), ['login' => 'csp-unknown', 'password' => 'invalid'])->assertRedirect();
        }
        $this->assertNoncedTags($this->post(route('login.store'), ['login' => 'csp-unknown', 'password' => 'invalid'])->assertTooManyRequests());
    }

    public function test_pusher_sources_are_limited_to_the_configured_cluster_and_do_not_expose_secrets(): void
    {
        config()->set('broadcasting.connections.pusher.options.cluster', 'ap1');
        config()->set('broadcasting.connections.pusher.secret', 'TOP-SECRET-DO-NOT-EXPOSE');
        $response = $this->get(route('login'))->assertOk();
        $connect = $this->directives($response)['connect-src'];
        $this->assertSame("'self' wss://ws-ap1.pusher.com https://sockjs-ap1.pusher.com", $connect);
        $this->assertStringNotContainsString('TOP-SECRET', $response->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString('*', $connect);
    }

    public function test_invalid_pusher_cluster_cannot_inject_policy_directives(): void
    {
        config()->set('broadcasting.connections.pusher.options.cluster', "eu; script-src 'unsafe-eval'");
        $response = $this->get(route('login'))->assertOk();
        $this->assertSame("'self'", $this->directives($response)['connect-src']);
        $this->assertStringNotContainsString('unsafe-eval', $response->headers->get('Content-Security-Policy'));
    }

    public function test_vite_development_server_is_allowed_only_in_local_environment(): void
    {
        $this->setHotFile('http://localhost:5173');
        $this->app->instance('env', 'local');
        $response = $this->get(route('login'))->assertOk();
        $directives = $this->directives($response);
        $this->assertStringContainsString('http://localhost:5173', $directives['script-src']);
        $this->assertStringContainsString('http://localhost:5173', $directives['style-src-elem']);
        $this->assertStringContainsString('ws://localhost:5173', $directives['connect-src']);
        $this->assertNoncedTags($response);
    }

    public function test_stale_hot_file_is_ignored_outside_local_environment(): void
    {
        $this->setHotFile('http://localhost:5173');
        $response = $this->get(route('login'))->assertOk();
        $this->assertStringNotContainsString('localhost:5173', $response->getContent());
        $this->assertStringNotContainsString('localhost:5173', $response->headers->get('Content-Security-Policy'));
        $this->assertNoncedTags($response);
        $this->assertSame($this->testHotFile, Vite::hotFile());
    }

    public function test_https_production_uses_upgrade_insecure_requests(): void
    {
        config()->set('app.url', 'https://risfm.oipi.test');
        $this->app->instance('env', 'production');
        $this->setHotFile('http://localhost:5173');
        $response = $this->get('https://risfm.oipi.test/login')->assertOk();
        $this->assertArrayHasKey('upgrade-insecure-requests', $this->directives($response));
        $this->assertStringNotContainsString('localhost:5173', $response->getContent());
    }

    public function test_middleware_never_adds_a_nonce_to_arbitrary_response_scripts(): void
    {
        Route::get('/__csp_injected', fn () => response('<script>window.injected=true;</script>'));
        $response = $this->get('/__csp_injected')->assertOk();
        $this->assertSame('<script>window.injected=true;</script>', $response->getContent());
        $this->assertStringContainsString("'nonce-", $this->directives($response)['script-src']);
    }

    public function test_json_validation_contract_is_preserved_with_the_full_policy(): void
    {
        $response = $this->postJson(route('login.store'), [])->assertUnprocessable()->assertJsonValidationErrors(['login', 'password']);
        $this->assertArrayHasKey('script-src', $this->directives($response));
    }

    public function test_future_web_scripts_and_styles_must_explicitly_include_the_nonce(): void
    {
        foreach (File::allFiles(resource_path('views')) as $file) {
            $path = $file->getPathname();
            if (str_contains($path, '/emails/') || str_ends_with($path, '/analytics/export-pdf.blade.php')) {
                continue;
            }
            preg_match_all('/<(?:script|style)\b[^>]*>/', File::get($path), $matches);
            foreach ($matches[0] as $tag) {
                $this->assertStringContainsString('nonce="{{ Vite::cspNonce() }}"', $tag, $path);
            }
        }
    }

    /** @return array<string, string> */
    private function directives(TestResponse $response): array
    {
        $directives = [];
        foreach (explode(';', $response->headers->get('Content-Security-Policy')) as $part) {
            [$name, $value] = array_pad(explode(' ', trim($part), 2), 2, '');
            $directives[$name] = $value;
        }

        return $directives;
    }

    private function nonce(TestResponse $response): string
    {
        preg_match("/'nonce-([^']+)'/", $this->directives($response)['script-src'], $matches);
        $this->assertArrayHasKey(1, $matches);

        return $matches[1];
    }

    private function assertNoncedTags(TestResponse $response): void
    {
        $nonce = $this->nonce($response);
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        foreach (['script', 'style'] as $name) {
            foreach ($document->getElementsByTagName($name) as $tag) {
                $this->assertSame($nonce, $tag->getAttribute('nonce'));
            }
        }
        foreach ($document->getElementsByTagName('*') as $element) {
            foreach ($element->attributes as $attribute) {
                $this->assertDoesNotMatchRegularExpression('/^on[a-z]+$/i', $attribute->name);
            }
        }
        $this->assertStringContainsString('<meta property="csp-nonce" nonce="'.$nonce.'">', $response->getContent());
    }

    private function setHotFile(string $url): void
    {
        $this->testHotFile = tempnam(sys_get_temp_dir(), 'risfm-csp-hot-');
        file_put_contents($this->testHotFile, $url);
        Vite::useHotFile($this->testHotFile);
    }
}
