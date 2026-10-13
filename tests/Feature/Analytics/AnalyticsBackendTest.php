<?php

namespace Tests\Feature\Analytics;

use App\Dto\Analytics\AnalyticsFiltersDTO;
use App\Enums\Administration\RoleCode;
use App\Enums\Audit\AuditAction;
use App\Enums\Mission\MissionResult;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Audit\AuditLogModel;
use App\Models\Mission\SearchHistoryModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Models\Registry\TitleTypeModel;
use App\Services\Analytics\AnalyticsService;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class AnalyticsBackendTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $service;

    private TitleTypeModel $patentType;

    private TitleTypeModel $trademarkType;

    private SearchLocationModel $location;

    private UserModel $responsible;

    /** @var array<string, MissingFormModel> */
    private array $forms = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AnalyticsService::class);
        $this->patentType = TitleTypeModel::factory()->create([
            'title_type_code' => 'BRV',
            'title_type_name' => 'Brevet',
        ]);
        $this->trademarkType = TitleTypeModel::factory()->create([
            'title_type_code' => 'MRQ',
            'title_type_name' => 'Marque',
        ]);
        $this->location = SearchLocationModel::factory()->create([
            'location_name' => 'Archives centrales',
        ]);
        $this->responsible = UserModel::factory()->create([
            'last_name' => 'KONE',
            'first_names' => 'Awa',
        ]);

        $this->createDataset();
    }

    public function test_report_calculates_global_kpis_and_all_breakdowns(): void
    {
        $report = $this->service->srv_report(new AnalyticsFiltersDTO);

        $this->assertSame(6, $report['kpis']['totalForms']);
        $this->assertSame(5, $report['kpis']['activeCampaignForms']);
        $this->assertSame(1, $report['kpis']['archivedForms']);
        $this->assertSame(3, $report['kpis']['resolvedForms']);
        $this->assertSame(1, $report['kpis']['enteredForms']);
        $this->assertSame(60.0, $report['kpis']['resolutionRate']);
        $this->assertSame(20.0, $report['kpis']['completionRate']);
        $this->assertSame(4, $report['kpis']['totalMissions']);
        $this->assertSame(2, $report['kpis']['activeMissions']);
        $this->assertSame(1, $report['kpis']['overdueMissions']);
        $this->assertSame(2, $report['kpis']['completedMissions']);

        $this->assertCount(count(FormStatus::cases()), $report['statusDistribution']);
        $this->assertSame(2, $report['titleTypeDistribution']->count());
        $this->assertSame(2, $report['locationPerformance']->first()['searches']);
        $this->assertSame(1, $report['locationPerformance']->first()['found']);
        $this->assertSame(50.0, $report['locationPerformance']->first()['successRate']);
        $this->assertSame('KONE Awa', $report['responsiblePerformance']->first()['label']);
        $this->assertCount(12, $report['monthlyTrend']);
        $this->assertSame(2, $report['monthlyTrend']->last()['searches']);
        $this->assertSame(1, $report['monthlyTrend']->last()['foundReports']);
        $this->assertSame(3, $report['monthlyTrend']->last()['resolvedForms']);
        $this->assertTrue($report['periodUsesRegistrationDate']);
    }

    public function test_filters_are_applied_consistently_to_kpis_and_distributions(): void
    {
        $report = $this->service->srv_report(new AnalyticsFiltersDTO(
            titleTypeCodeUnique: $this->patentType->title_type_code_unique,
        ));

        $this->assertSame(3, $report['kpis']['totalForms']);
        $this->assertSame(1, $report['kpis']['resolvedForms']);
        $this->assertSame(33.3, $report['kpis']['resolutionRate']);
        $this->assertCount(1, $report['titleTypeDistribution']);
        $this->assertSame('BRV', $report['titleTypeDistribution']->first()['code']);
        $this->assertSame(100.0, $report['titleTypeDistribution']->first()['percentage']);
    }

    public function test_empty_dataset_never_divides_by_zero(): void
    {
        $report = $this->service->srv_report(new AnalyticsFiltersDTO(formYear: 1900));

        $this->assertSame(0, $report['kpis']['totalForms']);
        $this->assertSame(0.0, $report['kpis']['resolutionRate']);
        $this->assertSame(0.0, $report['kpis']['completionRate']);
        $this->assertSame(0, $report['kpis']['totalMissions']);
    }

    public function test_dashboard_builds_global_operational_contract_for_administrator(): void
    {
        $administrator = $this->systemAdministrator();
        $urgentForm = $this->form(FormStatus::Missing, $this->patentType, 2024);
        $urgentForm->update([
            'priority' => FormPriority::Urgent,
            'last_responsible_user_code_unique' => null,
            'search_due_date' => today()->addDay(),
        ]);
        $activity = AuditLogModel::query()->create([
            'category' => AuditAction::FormCreated->category()->value,
            'action_code' => AuditAction::FormCreated->value,
            'actor_user_code_unique' => $administrator->user_code_unique,
            'actor_name' => trim("{$administrator->last_name} {$administrator->first_names}"),
            'subject_type' => 'missing_form',
            'subject_uuid' => $urgentForm->uuid,
            'subject_code_unique' => $urgentForm->missing_form_code_unique,
            'subject_label' => $urgentForm->form_number,
            'description' => 'Formulaire prioritaire créé.',
            'occurred_at' => now(),
        ]);

        $dashboard = $this->service->srv_dashboard($administrator);

        $this->assertSame('global', $dashboard['dashboardScope']);
        $this->assertTrue($dashboard['capabilities']['assignMissions']);
        $this->assertTrue($dashboard['capabilities']['reviewFoundReports']);
        $this->assertTrue($dashboard['capabilities']['finalizeForms']);
        $this->assertSame(1, $dashboard['attentionCounts']['formsWithoutMission']);
        $this->assertSame(1, $dashboard['attentionCounts']['overdueMissions']);
        $this->assertSame(0, $dashboard['attentionCounts']['dueSoonMissions']);
        $this->assertSame(0, $dashboard['attentionCounts']['pendingReviews']);
        $this->assertSame(1, $dashboard['attentionCounts']['awaitingDigitization']);
        $this->assertSame(1, $dashboard['attentionCounts']['awaitingEntry']);
        $this->assertSame(
            $urgentForm->uuid,
            $dashboard['attentionItems']['formsWithoutMission']->first()['uuid'],
        );
        $this->assertSame($urgentForm->uuid, $dashboard['priorityForms']->first()['uuid']);
        $this->assertSame($activity->uuid, $dashboard['recentActivity']->first()['uuid']);
        $this->assertCount(6, $dashboard['workflowProgress']);
        $this->assertCount(6, $dashboard['monthlyTrend']);
        $this->assertSame(7, $dashboard['monthlyTrend']->sum('registrations'));
    }

    public function test_dashboard_limits_operational_queues_to_search_agents_own_missions(): void
    {
        $this->systemAdministrator();
        $agentRole = RoleModel::query()
            ->where('role_code', RoleCode::SearchAgent->value)
            ->firstOrFail();
        $this->responsible->update([
            'role_code_unique' => $agentRole->role_code_unique,
            'must_change_password' => false,
        ]);
        $otherAgent = UserModel::factory()->create([
            'role_code_unique' => $agentRole->role_code_unique,
            'must_change_password' => false,
        ]);
        $otherForm = MissingFormModel::factory()->create([
            'title_type_code_unique' => $this->patentType->title_type_code_unique,
            'status' => FormStatus::InSearch,
            'priority' => FormPriority::Urgent,
            'last_responsible_user_code_unique' => $otherAgent->user_code_unique,
        ]);
        SearchMissionModel::factory()->create([
            'missing_form_code_unique' => $otherForm->missing_form_code_unique,
            'search_location_code_unique' => $this->location->search_location_code_unique,
            'responsible_user_code_unique' => $otherAgent->user_code_unique,
            'status' => MissionStatus::InProgress,
            'priority' => FormPriority::Urgent,
            'due_date' => today()->subDays(2),
        ]);

        $dashboard = $this->service->srv_dashboard($this->responsible->fresh());

        $this->assertSame('personal', $dashboard['dashboardScope']);
        $this->assertTrue($dashboard['capabilities']['viewMissionQueue']);
        $this->assertFalse($dashboard['capabilities']['assignMissions']);
        $this->assertSame(1, $dashboard['attentionCounts']['overdueMissions']);
        $this->assertCount(1, $dashboard['attentionItems']['overdueMissions']);
        $this->assertSame(
            $this->responsible->user_code_unique,
            $dashboard['attentionItems']['overdueMissions']->first()['responsibleCodeUnique'],
        );
        $this->assertSame(0, $dashboard['attentionCounts']['formsWithoutMission']);
        $this->assertSame(0, $dashboard['attentionCounts']['pendingReviews']);
        $this->assertEmpty($dashboard['recentActivity']);
    }

    public function test_read_only_dashboard_contains_no_operational_action_queue(): void
    {
        $this->systemAdministrator();
        $role = RoleModel::query()->where('role_code', RoleCode::ReadOnly->value)->firstOrFail();
        $viewer = UserModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
            'must_change_password' => false,
        ]);

        $dashboard = $this->service->srv_dashboard($viewer);

        $this->assertSame('overview', $dashboard['dashboardScope']);
        $this->assertFalse($dashboard['capabilities']['viewMissionQueue']);
        $this->assertSame([
            'formsWithoutMission' => 0,
            'overdueMissions' => 0,
            'dueSoonMissions' => 0,
            'pendingReviews' => 0,
            'awaitingDigitization' => 0,
            'awaitingEntry' => 0,
        ], $dashboard['attentionCounts']);
        $this->assertEmpty($dashboard['priorityForms']);
        $this->assertEmpty($dashboard['recentActivity']);
    }

    public function test_dashboard_route_exposes_backend_contract_to_the_view(): void
    {
        $administrator = $this->systemAdministrator();

        $this->actingAs($administrator)
            ->withSession(['auth_session_version' => $administrator->session_version])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHasAll([
                'kpis',
                'dashboardScope',
                'capabilities',
                'attentionCounts',
                'attentionItems',
                'workflowProgress',
                'monthlyTrend',
                'priorityForms',
                'recentActivity',
            ]);
    }

    public function test_analytics_route_and_permission_are_registered(): void
    {
        $route = Route::getRoutes()->getByName('analytics.index');

        $this->assertNotNull($route);
        $this->assertContains('GET', $route->methods());
        $this->assertContains('permission:analytics_view', $route->gatherMiddleware());

        foreach (['analytics.export.excel', 'analytics.export.pdf'] as $routeName) {
            $exportRoute = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($exportRoute);
            $this->assertContains('GET', $exportRoute->methods());
            $this->assertContains('permission:analytics_view', $exportRoute->gatherMiddleware());
        }
    }

    public function test_authorized_user_can_render_analytics_page(): void
    {
        $administrator = $this->systemAdministrator();

        $this->actingAs($administrator)
            ->withSession(['auth_session_version' => $administrator->session_version])
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSeeText('Statistiques & Pilotage du registre')
            ->assertSee(route('analytics.export.excel'), false)
            ->assertSee(route('analytics.export.pdf'), false);
    }

    public function test_excel_export_is_a_real_filtered_xlsx_workbook(): void
    {
        $administrator = $this->systemAdministrator();
        $response = $this->actingAs($administrator)
            ->withSession(['auth_session_version' => $administrator->session_version])
            ->get(route('analytics.export.excel', [
                'title_type_code_unique' => $this->patentType->title_type_code_unique,
                'trend_months' => 6,
            ]))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );

        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', $content);
        $this->assertDatabaseHas('aud_audit_log_models', [
            'action_code' => AuditAction::AnalyticsExported->value,
            'actor_user_code_unique' => $administrator->user_code_unique,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'risfm-xlsx-');
        $this->assertNotFalse($path);

        try {
            file_put_contents($path, $content);
            $workbook = IOFactory::load($path);

            $this->assertSame(
                ['Synthèse', 'Statuts', 'Types de titres', 'Localisations', 'Responsables', 'Évolution mensuelle', 'Années des titres'],
                $workbook->getSheetNames(),
            );
            $this->assertStringContainsString(
                'Brevet',
                (string) $workbook->getSheetByName('Synthèse')?->getCell('B7')->getValue(),
            );
            $this->assertSame(
                'BRV',
                $workbook->getSheetByName('Types de titres')?->getCell('A4')->getValue(),
            );
            $this->assertSame(
                3,
                $workbook->getSheetByName('Types de titres')?->getCell('C4')->getValue(),
            );
            $workbook->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
    }

    public function test_pdf_export_is_a_real_filtered_pdf_document(): void
    {
        $administrator = $this->systemAdministrator();
        $response = $this->actingAs($administrator)
            ->withSession(['auth_session_version' => $administrator->session_version])
            ->get(route('analytics.export.pdf', [
                'status' => FormStatus::Found->value,
                'trend_months' => 6,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }

    public function test_invalid_trend_period_is_rejected_before_rendering_the_view(): void
    {
        $administrator = $this->systemAdministrator();

        $this->actingAs($administrator)
            ->withSession(['auth_session_version' => $administrator->session_version])
            ->from('/dashboard')
            ->get(route('analytics.index', ['trend_months' => 7]))
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors('trend_months');
    }

    public function test_user_without_analytics_permission_is_forbidden(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $role = RoleModel::query()->where('role_code', RoleCode::SearchAgent->value)->firstOrFail();
        $agent = UserModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
            'must_change_password' => false,
        ]);

        $this->actingAs($agent)
            ->withSession(['auth_session_version' => $agent->session_version]);

        foreach (['analytics.index', 'analytics.export.excel', 'analytics.export.pdf'] as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }
    }

    private function createDataset(): void
    {
        $this->forms = [
            'missing' => $this->form(FormStatus::Missing, $this->patentType, 2017),
            'in_search' => $this->form(FormStatus::InSearch, $this->patentType, 2018),
            'found' => $this->form(FormStatus::Found, $this->patentType, 2019, true),
            'digitized' => $this->form(FormStatus::Digitized, $this->trademarkType, 2020, true),
            'entered' => $this->form(FormStatus::Entered, $this->trademarkType, 2021, true),
            'archived' => $this->form(FormStatus::Archived, $this->trademarkType, 2022),
        ];

        $this->mission($this->forms['missing'], MissionStatus::Assigned, now()->addDays(3));
        $this->mission($this->forms['in_search'], MissionStatus::InProgress, now()->subDay());
        $foundMission = $this->mission(
            $this->forms['found'],
            MissionStatus::Completed,
            now()->subDays(2),
            MissionResult::Found,
        );
        $notFoundMission = $this->mission(
            $this->forms['digitized'],
            MissionStatus::Completed,
            now()->subDays(3),
            MissionResult::NotFound,
        );

        $this->history($foundMission, MissionResult::Found, 'Formulaire retrouvé.');
        $this->history($notFoundMission, MissionResult::NotFound, 'Recherche infructueuse.');
    }

    private function form(
        FormStatus $status,
        TitleTypeModel $titleType,
        int $year,
        bool $resolved = false,
    ): MissingFormModel {
        return MissingFormModel::factory()->create([
            'title_type_code_unique' => $titleType->title_type_code_unique,
            'form_year' => $year,
            'status' => $status,
            'last_search_location_code_unique' => $this->location->search_location_code_unique,
            'last_responsible_user_code_unique' => $this->responsible->user_code_unique,
            'resolved_at' => $resolved ? now() : null,
            'created_at' => now()->subMonths(2),
        ]);
    }

    private function mission(
        MissingFormModel $form,
        MissionStatus $status,
        mixed $dueDate,
        ?MissionResult $result = null,
    ): SearchMissionModel {
        return SearchMissionModel::factory()->create([
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'search_location_code_unique' => $this->location->search_location_code_unique,
            'responsible_user_code_unique' => $this->responsible->user_code_unique,
            'status' => $status,
            'result' => $result,
            'due_date' => $dueDate->toDateString(),
            'searched_on' => $status === MissionStatus::Completed ? today() : null,
            'closed_at' => $status === MissionStatus::Completed ? now() : null,
        ]);
    }

    private function history(
        SearchMissionModel $mission,
        MissionResult $result,
        string $summary,
    ): void {
        SearchHistoryModel::query()->create([
            'search_mission_code_unique' => $mission->search_mission_code_unique,
            'missing_form_code_unique' => $mission->missing_form_code_unique,
            'tracking_cycle' => 1,
            'search_location_code_unique' => $this->location->search_location_code_unique,
            'search_location_name' => $this->location->location_name,
            'responsible_user_code_unique' => $this->responsible->user_code_unique,
            'responsible_name' => 'KONE Awa',
            'form_status' => $mission->missingForm->status,
            'mission_result' => $result,
            'searched_on' => today(),
            'result_summary' => $summary,
            'recorded_by_user_code_unique' => $this->responsible->user_code_unique,
            'recorded_by_name' => 'KONE Awa',
            'source' => 'mission_result',
        ]);
    }

    private function systemAdministrator(): UserModel
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $role = RoleModel::query()->where('role_code', RoleCode::SystemAdmin->value)->firstOrFail();

        return UserModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
            'must_change_password' => false,
        ]);
    }
}
