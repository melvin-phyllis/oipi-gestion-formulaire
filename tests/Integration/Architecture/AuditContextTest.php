<?php

namespace Tests\Integration\Architecture;

use App\Contracts\Audit\AuditContextProviderInterface;
use App\Dto\Audit\AuditRequestContextDTO;
use App\Enums\Audit\AuditAction;
use App\Models\Audit\AuditLogModel;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AuditContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--no-interaction' => true])->assertExitCode(0);
    }

    public function test_audit_context_is_captured_for_the_current_http_request(): void
    {
        Route::middleware('web')->get('/test-audit-context', function (): string {
            app(AuditLogger::class)->srv_record(AuditAction::AuthLoginFailed, 'Audit de test.');

            return 'ok';
        })->name('test.audit.context');

        $this->withHeader('User-Agent', 'ArchitectureTest')
            ->get('/test-audit-context')->assertOk();

        $log = AuditLogModel::query()->sole();
        $this->assertSame('test.audit.context', $log->route_name);
        $this->assertSame('GET', $log->http_method);
        $this->assertSame('/test-audit-context', $log->request_path);
        $this->assertSame('ArchitectureTest', $log->user_agent);
        $this->assertNotNull($log->ip_address);
    }

    public function test_background_work_without_a_route_has_no_synthetic_http_context(): void
    {
        $this->app->instance('request', Request::create('http://localhost'));

        $log = app(AuditLogger::class)->srv_record(AuditAction::AuthLoginFailed, 'Traitement console.');

        $this->assertNull($log->route_name);
        $this->assertNull($log->http_method);
        $this->assertNull($log->request_path);
        $this->assertNull($log->ip_address);
    }

    public function test_audit_service_accepts_context_from_a_non_http_provider(): void
    {
        $this->mock(AuditContextProviderInterface::class)
            ->shouldReceive('context')->once()->andReturn(new AuditRequestContextDTO(
                routeName: 'saved.context',
                httpMethod: 'POST',
                requestPath: '/missions',
                ipAddress: '192.0.2.1',
                userAgent: 'test-agent',
            ));

        $log = app(AuditLogger::class)->srv_record(AuditAction::AuthLoginFailed, 'Contexte fourni.');

        $this->assertSame('saved.context', $log->route_name);
        $this->assertSame('192.0.2.1', $log->ip_address);
    }
}
