<?php

namespace Tests\Feature\Audit;

use App\Enums\Administration\RoleCode;
use App\Enums\Audit\AuditAction;
use App\Events\Organization\DirectorateDeleted;
use App\Events\Organization\DirectorateRestored;
use App\Events\Organization\ServiceDeleted;
use App\Events\Organization\ServiceRestored;
use App\Events\Registry\MissingFormCreated;
use App\Events\Registry\ReferenceItemDeleted;
use App\Events\Registry\ReferenceItemRestored;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Audit\AuditLogModel;
use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\TitleTypeModel;
use App\Services\Audit\AuditLogger;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use LogicException;
use Tests\TestCase;

final class AuditBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_event_creates_one_immutable_audit_entry_with_snapshots(): void
    {
        $actor = UserModel::factory()->create(['last_name' => 'KONE', 'first_names' => 'Awa']);
        $form = MissingFormModel::factory()->create(['form_number' => 'B-2026-000001']);

        MissingFormCreated::dispatch($form->uuid, $actor->uuid);

        $this->assertDatabaseCount('aud_audit_log_models', 1);
        $log = AuditLogModel::query()->sole();
        $this->assertSame(AuditAction::FormCreated->value, $log->action_code);
        $this->assertSame($actor->user_code_unique, $log->actor_user_code_unique);
        $this->assertSame('KONE Awa', $log->actor_name);
        $this->assertSame($form->missing_form_code_unique, $log->subject_code_unique);
        $this->assertSame('B-2026-000001', $log->subject_label);

        $this->expectException(LogicException::class);
        $log->description = 'Altération interdite';
        $log->save();
    }

    public function test_sensitive_metadata_is_never_persisted(): void
    {
        $log = app(AuditLogger::class)->srv_record(
            AuditAction::AuthLoginFailed,
            'Tentative échouée.',
            metadata: [
                'password' => 'secret',
                'token' => 'secret-token',
                'safe_value' => 'conservée',
                'nested' => [
                    'current_password' => 'secret',
                    'source' => 'test',
                ],
            ],
        );

        $this->assertArrayNotHasKey('password', $log->metadata);
        $this->assertArrayNotHasKey('token', $log->metadata);
        $this->assertArrayNotHasKey('current_password', $log->metadata['nested']);
        $this->assertSame('conservée', $log->metadata['safe_value']);
        $this->assertSame('test', $log->metadata['nested']['source']);
    }

    public function test_organization_and_reference_lifecycle_events_are_audited(): void
    {
        $actor = UserModel::factory()->create();
        $directorate = DirectorateModel::factory()->create();
        $service = ServiceModel::factory()->create([
            'directorate_code_unique' => $directorate->directorate_code_unique,
        ]);
        $titleType = TitleTypeModel::factory()->create();

        DirectorateDeleted::dispatch($directorate->uuid, $actor->uuid);
        DirectorateRestored::dispatch($directorate->uuid, $actor->uuid);
        ServiceDeleted::dispatch($service->uuid, $actor->uuid);
        ServiceRestored::dispatch($service->uuid, $actor->uuid);
        ReferenceItemDeleted::dispatch('title_type', $titleType->uuid, $actor->uuid);
        ReferenceItemRestored::dispatch('title_type', $titleType->uuid, $actor->uuid);

        foreach ([
            AuditAction::DirectorateDeleted,
            AuditAction::DirectorateRestored,
            AuditAction::ServiceDeleted,
            AuditAction::ServiceRestored,
            AuditAction::ReferenceDeleted,
            AuditAction::ReferenceRestored,
        ] as $action) {
            $this->assertDatabaseHas('aud_audit_log_models', [
                'action_code' => $action->value,
                'actor_user_code_unique' => $actor->user_code_unique,
            ]);
        }
    }

    public function test_successful_login_failed_login_and_logout_are_audited(): void
    {
        $user = UserModel::factory()->create([
            'username' => 'agent.audit',
            'password' => Hash::make('Correct_Password2026#'),
            'must_change_password' => false,
        ]);

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'incorrect',
        ])->assertSessionHasErrors('login');

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'Correct_Password2026#',
        ])->assertRedirect(route('dashboard'));

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertSame(1, AuditLogModel::query()->where('action_code', AuditAction::AuthLoginFailed->value)->count());
        $this->assertSame(1, AuditLogModel::query()->where('action_code', AuditAction::AuthLoginSucceeded->value)->count());
        $this->assertSame(1, AuditLogModel::query()->where('action_code', AuditAction::AuthLogout->value)->count());

        $failed = AuditLogModel::query()->where('action_code', AuditAction::AuthLoginFailed->value)->sole();
        $this->assertNull($failed->actor_user_code_unique);
        $this->assertArrayHasKey('login_fingerprint', $failed->metadata);
        $this->assertSame(64, strlen($failed->metadata['login_fingerprint']));
    }

    public function test_audit_route_is_filtered_and_protected_by_permission(): void
    {
        $this->seedAuthorization();
        $administrator = $this->userForRole(RoleCode::SystemAdmin);
        $registryManager = $this->userForRole(RoleCode::RegistryManager);
        $reader = $this->userForRole(RoleCode::ReadOnly);
        $logger = app(AuditLogger::class);
        $logger->srv_record(AuditAction::FormCreated, 'Formulaire témoin créé.');
        $logger->srv_record(AuditAction::MissionAssigned, 'Mission témoin affectée.');

        $route = Route::getRoutes()->getByName('audit.index');
        $this->assertNotNull($route);
        $this->assertContains('permission:audit_view', $route->gatherMiddleware());

        $this->actingAs($administrator)
            ->withSession(['auth_session_version' => $administrator->session_version])
            ->get(route('audit.index', ['action' => AuditAction::FormCreated->value]))
            ->assertOk()
            ->assertSee('Formulaire témoin créé.')
            ->assertDontSee('Mission témoin affectée.');

        $this->actingAs($reader)
            ->withSession(['auth_session_version' => $reader->session_version])
            ->get(route('audit.index'))
            ->assertForbidden();

        $this->actingAs($registryManager)
            ->withSession(['auth_session_version' => $registryManager->session_version])
            ->get(route('audit.index'))
            ->assertForbidden();
    }

    private function seedAuthorization(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function userForRole(RoleCode $role): UserModel
    {
        return UserModel::factory()->create([
            'role_code_unique' => RoleModel::query()
                ->where('role_code', $role->value)
                ->valueOrFail('role_code_unique'),
            'must_change_password' => false,
        ]);
    }
}
