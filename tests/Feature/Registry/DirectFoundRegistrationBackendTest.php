<?php

namespace Tests\Feature\Registry;

use App\Dto\Registry\RegisterFoundMissingFormDTO;
use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Enums\Audit\AuditAction;
use App\Enums\Registry\FinderType;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Events\Registry\MissingFormRegisteredAsFound;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Audit\AuditLogModel;
use App\Models\Registry\DirectFoundReportModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Models\Registry\TitleTypeModel;
use App\Services\Registry\DirectFoundRegistrationService;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

final class DirectFoundRegistrationBackendTest extends TestCase
{
    use RefreshDatabase;

    private DirectFoundRegistrationService $service;

    private UserModel $manager;

    private UserModel $agent;

    private TitleTypeModel $titleType;

    private SearchLocationModel $location;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-13 10:00:00');
        $this->seed(DatabaseSeeder::class);
        $this->service = app(DirectFoundRegistrationService::class);
        $this->manager = $this->userForRole(RoleCode::RegistryManager);
        $this->agent = $this->userForRole(RoleCode::SearchAgent);
        $this->titleType = TitleTypeModel::factory()->create();
        $this->location = SearchLocationModel::factory()->create();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_internal_finder_is_snapshotted_and_form_is_found_without_fake_mission(): void
    {
        Event::fake([MissingFormRegisteredAsFound::class]);
        $finder = UserModel::factory()->create([
            'last_name' => 'KONE',
            'first_names' => 'Awa',
        ]);

        $form = $this->service->srv_register(
            $this->dto(
                formNumber: 'B-2026-FOUND-001',
                finderType: FinderType::Internal,
                finderUserCodeUnique: $finder->user_code_unique,
            ),
            $this->manager,
        );

        $form->load('directFoundReport');
        $report = $form->directFoundReport;
        $this->assertSame(FormStatus::Found, $form->status);
        $this->assertSame('2026-10-10', $form->resolved_at?->toDateString());
        $this->assertSame($this->location->search_location_code_unique, $form->last_search_location_code_unique);
        $this->assertNull($form->last_responsible_user_code_unique);
        $this->assertDatabaseCount('mis_search_mission_models', 0);
        $this->assertInstanceOf(DirectFoundReportModel::class, $report);
        $this->assertSame(FinderType::Internal, $report->finder_type);
        $this->assertSame($finder->user_code_unique, $report->finder_user_code_unique);
        $this->assertSame('KONE Awa', $report->finder_name);
        $this->assertSame($finder->service?->service_name, $report->finder_organization);
        $this->assertSame($this->manager->user_code_unique, $report->recorded_by_user_code_unique);
        Event::assertDispatched(MissingFormRegisteredAsFound::class);
    }

    public function test_external_finder_and_audit_are_persisted_with_snapshots(): void
    {
        $form = $this->service->srv_register(
            $this->dto(
                formNumber: 'M-2025-FOUND-002',
                finderType: FinderType::External,
                finderName: 'YAO Clarisse',
                finderOrganization: 'Cabinet Conseil PI',
                finderContact: '+225 01 02 03 04 05',
            ),
            $this->manager,
        );

        $report = $form->directFoundReport()->sole();
        $this->assertNull($report->finder_user_code_unique);
        $this->assertSame('YAO Clarisse', $report->finder_name);
        $this->assertSame('Cabinet Conseil PI', $report->finder_organization);
        $this->assertSame('+225 01 02 03 04 05', $report->finder_contact);

        $audit = AuditLogModel::query()
            ->where('action_code', AuditAction::FormRegisteredAsFound->value)
            ->sole();
        $this->assertSame($form->missing_form_code_unique, $audit->subject_code_unique);
        $this->assertSame(FinderType::External->value, $audit->metadata['finder_type']);
        $this->assertSame($report->uuid, $audit->metadata['report_uuid']);
    }

    public function test_future_discovery_and_duplicate_business_identity_are_rejected(): void
    {
        $future = $this->dto(
            formNumber: 'B-2026-FUTURE',
            finderType: FinderType::External,
            finderName: 'Personne externe',
            foundOn: CarbonImmutable::tomorrow(),
        );

        try {
            $this->service->srv_register($future, $this->manager);
            $this->fail('La date future aurait dû être refusée.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('found_on', $exception->errors());
        }

        $valid = $this->dto(
            formNumber: 'B-2026-DUPLICATE',
            finderType: FinderType::External,
            finderName: 'Personne externe',
        );
        $this->service->srv_register($valid, $this->manager);

        $this->expectException(ValidationException::class);
        $this->service->srv_register($valid, $this->manager);
    }

    public function test_report_is_immutable(): void
    {
        $form = $this->service->srv_register(
            $this->dto(
                formNumber: 'B-2026-IMMUTABLE',
                finderType: FinderType::External,
                finderName: 'Personne externe',
            ),
            $this->manager,
        );
        $report = $form->directFoundReport()->sole();

        $this->expectException(LogicException::class);
        $report->finder_name = 'Nom altéré';
        $report->save();
    }

    public function test_route_is_explicitly_protected_and_only_manager_can_register_found_form(): void
    {
        $route = Route::getRoutes()->getByName('registry.forms.store-found');
        $this->assertNotNull($route);
        $this->assertContains('permission:'.PermissionCode::RegistryRegisterFound->value, $route->gatherMiddleware());

        $payload = $this->payload('M-2026-HTTP-001');
        $this->actingAs($this->agent)
            ->withSession(['auth_session_version' => $this->agent->session_version])
            ->post(route('registry.forms.store-found'), $payload)
            ->assertForbidden();

        $response = $this->actingAs($this->manager)
            ->withSession(['auth_session_version' => $this->manager->session_version])
            ->post(route('registry.forms.store-found'), $payload);

        $form = MissingFormModel::query()->where('form_number', 'M-2026-HTTP-001')->sole();
        $response->assertRedirect(route('registry.forms.show', $form));
        $this->assertSame(FormStatus::Found, $form->status);
    }

    public function test_external_request_requires_name_and_rejects_internal_account_reference(): void
    {
        $payload = $this->payload('M-2026-INVALID');
        $payload['finder_name'] = '';
        $payload['finder_user_code_unique'] = $this->agent->user_code_unique;

        $this->actingAs($this->manager)
            ->withSession(['auth_session_version' => $this->manager->session_version])
            ->post(route('registry.forms.store-found'), $payload)
            ->assertSessionHasErrors(['finder_name', 'finder_user_code_unique']);

        $this->assertDatabaseMissing('reg_missing_form_models', ['form_number' => 'M-2026-INVALID']);
    }

    public function test_create_and_show_views_render_direct_found_registration_workflow(): void
    {
        $this->actingAs($this->manager)
            ->withSession(['auth_session_version' => $this->manager->session_version])
            ->get(route('registry.forms.index'))
            ->assertOk()
            ->assertSee('Nouveau formulaire manquant')
            ->assertDontSee(route('registry.forms.create', ['mode' => 'found']), false);

        $this->actingAs($this->manager)
            ->withSession(['auth_session_version' => $this->manager->session_version])
            ->get(route('registry.forms.create', ['mode' => 'found']))
            ->assertOk()
            ->assertSee('Enregistrer un formulaire déjà retrouvé')
            ->assertSee('id="tab-btn-missing"', false)
            ->assertSee('id="tab-btn-found"', false)
            ->assertSee(route('registry.forms.store-found'), false)
            ->assertSee('finder_type', false)
            ->assertSee('found_location_code_unique', false)
            ->assertSee('found_on', false)
            ->assertSee('result_summary', false);

        $form = $this->service->srv_register(
            $this->dto(
                formNumber: 'M-2026-SHOW-001',
                finderType: FinderType::External,
                finderName: 'YAO Clarisse',
                finderOrganization: 'Cabinet Conseil PI',
                finderContact: '+225 01 02 03 04 05',
            ),
            $this->manager,
        );

        $report = $form->directFoundReport()->sole();

        $this->actingAs($this->manager)
            ->withSession(['auth_session_version' => $this->manager->session_version])
            ->get(route('registry.forms.show', $form))
            ->assertOk()
            ->assertSee('Découverte directe')
            ->assertSee($report->direct_found_report_code_unique)
            ->assertSee('YAO Clarisse')
            ->assertSee('Cabinet Conseil PI')
            ->assertSee('+225 01 02 03 04 05')
            ->assertSee('Enregistrement direct comme formulaire retrouvé')
            ->assertSee('10/10/2026');
    }

    public function test_search_agent_only_sees_the_standard_missing_form_creation_mode(): void
    {
        $this->actingAs($this->agent)
            ->withSession(['auth_session_version' => $this->agent->session_version])
            ->get(route('registry.forms.create', ['mode' => 'found']))
            ->assertOk()
            ->assertSee('Déclarer un formulaire manquant')
            ->assertDontSee('id="tab-btn-found"', false)
            ->assertDontSee(route('registry.forms.store-found'), false);
    }

    private function dto(
        string $formNumber,
        FinderType $finderType,
        ?string $finderUserCodeUnique = null,
        ?string $finderName = null,
        ?string $finderOrganization = null,
        ?string $finderContact = null,
        ?CarbonImmutable $foundOn = null,
    ): RegisterFoundMissingFormDTO {
        return new RegisterFoundMissingFormDTO(
            titleTypeCodeUnique: $this->titleType->title_type_code_unique,
            formYear: 2026,
            formNumber: $formNumber,
            priority: FormPriority::High,
            finderType: $finderType,
            finderUserCodeUnique: $finderUserCodeUnique,
            finderName: $finderName,
            finderOrganization: $finderOrganization,
            finderContact: $finderContact,
            foundLocationCodeUnique: $this->location->search_location_code_unique,
            foundOn: $foundOn ?? CarbonImmutable::parse('2026-10-10'),
            resultSummary: 'Le formulaire original a été retrouvé et identifié.',
        );
    }

    /** @return array<string, mixed> */
    private function payload(string $formNumber): array
    {
        return [
            'title_type_code_unique' => $this->titleType->title_type_code_unique,
            'form_year' => 2026,
            'form_number' => $formNumber,
            'priority' => FormPriority::High->value,
            'finder_type' => FinderType::External->value,
            'finder_name' => 'YAO Clarisse',
            'finder_organization' => 'Cabinet Conseil PI',
            'finder_contact' => 'contact@example.test',
            'found_location_code_unique' => $this->location->search_location_code_unique,
            'found_on' => '2026-10-10',
            'result_summary' => 'Le formulaire original a été retrouvé et identifié.',
        ];
    }

    private function userForRole(RoleCode $roleCode): UserModel
    {
        return UserModel::factory()->create([
            'role_code_unique' => RoleModel::query()
                ->where('role_code', $roleCode->value)
                ->valueOrFail('role_code_unique'),
            'must_change_password' => false,
        ]);
    }
}
