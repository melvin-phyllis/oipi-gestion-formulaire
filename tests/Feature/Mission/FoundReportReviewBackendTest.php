<?php

namespace Tests\Feature\Mission;

use App\Dto\Mission\AssignSearchMissionDTO;
use App\Dto\Mission\RecordMissionResultDTO;
use App\Dto\Mission\ReviewFoundReportDTO;
use App\Enums\Administration\RoleCode;
use App\Enums\Mission\FoundReportDecision;
use App\Enums\Mission\MissionResult;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Events\Mission\FoundReportConfirmed;
use App\Events\Mission\FoundReportRejected;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Services\Mission\FoundReportReviewService;
use App\Services\Mission\SearchMissionReadService;
use App\Services\Mission\SearchMissionService;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use LogicException;
use Tests\TestCase;

final class FoundReportReviewBackendTest extends TestCase
{
    use RefreshDatabase;

    private SearchMissionService $missions;

    private FoundReportReviewService $reviews;

    private UserModel $manager;

    private UserModel $agent;

    private MissingFormModel $form;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-12 10:00:00');
        $this->seed(DatabaseSeeder::class);
        $this->missions = app(SearchMissionService::class);
        $this->reviews = app(FoundReportReviewService::class);
        $this->manager = $this->userForRole(RoleCode::RegistryManager);
        $this->agent = $this->userForRole(RoleCode::SearchAgent);
        $this->form = MissingFormModel::factory()->create();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_manager_confirms_found_report_and_closes_parallel_work(): void
    {
        Event::fake([FoundReportConfirmed::class]);
        $reportedMission = $this->assign($this->agent, SearchLocationModel::factory()->create());
        $parallelMission = $this->assign(
            $this->userForRole(RoleCode::SearchAgent),
            SearchLocationModel::factory()->create(),
        );
        $reportedMission = $this->recordFound($reportedMission);

        $review = $this->reviews->srv_confirm(
            $reportedMission,
            ReviewFoundReportDTO::confirmed(),
            $this->manager,
        );

        $this->assertSame(FoundReportDecision::Confirmed, $review->decision);
        $this->assertSame($reportedMission->search_mission_code_unique, $review->search_mission_code_unique);
        $this->assertSame($this->manager->user_code_unique, $review->reviewed_by_user_code_unique);
        $this->assertSame(MissionStatus::Cancelled, $parallelMission->refresh()->status);
        $this->assertSame('Signalement « Retrouvé » confirmé par un responsable.', $parallelMission->cancellation_reason);
        $this->form->refresh();
        $this->assertSame(FormStatus::Found, $this->form->status);
        $this->assertNotNull($this->form->resolved_at);
        $this->assertNull($this->form->search_due_date);
        Event::assertDispatched(FoundReportConfirmed::class);
    }

    public function test_manager_can_confirm_a_result_that_requires_verification(): void
    {
        Event::fake([FoundReportConfirmed::class]);
        $reportedMission = $this->assign($this->agent, SearchLocationModel::factory()->create());
        $reportedMission = $this->recordNeedsVerification($reportedMission);

        $displayData = app(SearchMissionReadService::class)->srv_showData($reportedMission, $this->manager);

        $this->assertTrue($displayData['canReviewFound']);

        $review = $this->reviews->srv_confirm(
            $reportedMission,
            ReviewFoundReportDTO::confirmed(),
            $this->manager,
        );

        $this->assertSame(FoundReportDecision::Confirmed, $review->decision);
        $this->assertSame(FormStatus::Found, $this->form->refresh()->status);
        $this->assertNotNull($this->form->resolved_at);
        Event::assertDispatched(FoundReportConfirmed::class);
    }

    public function test_manager_rejects_found_report_and_keeps_parallel_search_active(): void
    {
        Event::fake([FoundReportRejected::class]);
        $reportedMission = $this->assign($this->agent, SearchLocationModel::factory()->create());
        $parallelMission = $this->assign(
            $this->userForRole(RoleCode::SearchAgent),
            SearchLocationModel::factory()->create(),
        );
        $reportedMission = $this->recordFound($reportedMission);

        $review = $this->reviews->srv_reject(
            $reportedMission,
            ReviewFoundReportDTO::rejected([
                'rejection_reason' => 'Le document présenté est seulement une photocopie.',
            ]),
            $this->manager,
        );

        $this->assertSame(FoundReportDecision::Rejected, $review->decision);
        $this->assertSame('Le document présenté est seulement une photocopie.', $review->rejection_reason);
        $this->assertSame(MissionStatus::Assigned, $parallelMission->refresh()->status);
        $this->assertSame(FormStatus::InSearch, $this->form->refresh()->status);
        $this->assertNull($this->form->resolved_at);
        Event::assertDispatched(FoundReportRejected::class);
    }

    public function test_rejection_without_parallel_work_returns_form_to_missing(): void
    {
        $reportedMission = $this->reportFound();

        $this->reviews->srv_reject(
            $reportedMission,
            ReviewFoundReportDTO::rejected([
                'rejection_reason' => 'Le numéro du formulaire ne correspond pas.',
            ]),
            $this->manager,
        );

        $this->assertSame(FormStatus::Missing, $this->form->refresh()->status);
        $this->assertNull($this->form->search_due_date);
    }

    public function test_manager_can_reject_a_result_that_requires_verification(): void
    {
        Event::fake([FoundReportRejected::class]);
        $reportedMission = $this->assign($this->agent, SearchLocationModel::factory()->create());
        $reportedMission = $this->recordNeedsVerification($reportedMission);

        $review = $this->reviews->srv_reject(
            $reportedMission,
            ReviewFoundReportDTO::rejected([
                'rejection_reason' => 'Le document contrôlé ne correspond pas au formulaire recherché.',
            ]),
            $this->manager,
        );

        $this->assertSame(FoundReportDecision::Rejected, $review->decision);
        $this->assertSame(FormStatus::Missing, $this->form->refresh()->status);
        $this->assertNull($this->form->resolved_at);
        Event::assertDispatched(FoundReportRejected::class);
    }

    public function test_found_report_can_only_be_reviewed_once(): void
    {
        $reportedMission = $this->reportFound();
        $this->reviews->srv_confirm(
            $reportedMission,
            ReviewFoundReportDTO::confirmed(),
            $this->manager,
        );

        $this->expectException(LogicException::class);
        $this->reviews->srv_confirm(
            $reportedMission,
            ReviewFoundReportDTO::confirmed(),
            $this->manager,
        );
    }

    public function test_search_agent_cannot_review_found_report(): void
    {
        $reportedMission = $this->reportFound();

        $this->expectException(AuthorizationException::class);
        $this->reviews->srv_confirm(
            $reportedMission,
            ReviewFoundReportDTO::confirmed(),
            $this->agent,
        );
    }

    public function test_new_assignment_is_blocked_while_found_report_is_pending(): void
    {
        $this->reportFound();

        $this->expectException(LogicException::class);
        $this->assign(
            $this->userForRole(RoleCode::SearchAgent),
            SearchLocationModel::factory()->create(),
        );
    }

    public function test_new_assignment_is_blocked_while_verification_result_is_pending(): void
    {
        $mission = $this->assign($this->agent, SearchLocationModel::factory()->create());
        $this->recordNeedsVerification($mission);

        $this->expectException(LogicException::class);
        $this->assign(
            $this->userForRole(RoleCode::SearchAgent),
            SearchLocationModel::factory()->create(),
        );
    }

    public function test_http_route_requires_review_permission(): void
    {
        $reportedMission = $this->reportFound();

        $this->actingAs($this->agent)
            ->withSession(['auth_session_version' => $this->agent->session_version])
            ->patch(route('missions.found.confirm', $reportedMission))
            ->assertForbidden();

        $this->assertDatabaseCount('mis_found_report_review_models', 0);
    }

    public function test_manager_can_confirm_found_report_through_http_route(): void
    {
        $reportedMission = $this->reportFound();

        $this->actingAs($this->manager)
            ->withSession(['auth_session_version' => $this->manager->session_version])
            ->patch(route('missions.found.confirm', $reportedMission))
            ->assertRedirect();

        $this->assertDatabaseHas('mis_found_report_review_models', [
            'search_mission_code_unique' => $reportedMission->search_mission_code_unique,
            'decision' => FoundReportDecision::Confirmed->value,
            'reviewed_by_user_code_unique' => $this->manager->user_code_unique,
        ]);
        $this->assertSame(FormStatus::Found, $this->form->refresh()->status);
    }

    public function test_parallel_result_does_not_clear_pending_found_report(): void
    {
        $reportedMission = $this->assign($this->agent, SearchLocationModel::factory()->create());
        $parallelAgent = $this->userForRole(RoleCode::SearchAgent);
        $parallelMission = $this->assign($parallelAgent, SearchLocationModel::factory()->create());
        $this->recordFound($reportedMission);

        $this->missions->srv_recordResult(
            $parallelMission,
            new RecordMissionResultDTO(
                result: MissionResult::NotFound,
                resultSummary: 'Aucun formulaire dans cette seconde localisation.',
                searchedOn: CarbonImmutable::today(),
            ),
            $parallelAgent,
        );

        $this->assertSame(FormStatus::NeedsVerification, $this->form->refresh()->status);
        $this->assertDatabaseCount('mis_found_report_review_models', 0);
    }

    private function reportFound(): SearchMissionModel
    {
        $mission = $this->assign($this->agent, SearchLocationModel::factory()->create());

        return $this->recordFound($mission);
    }

    private function recordFound(SearchMissionModel $mission): SearchMissionModel
    {
        return $this->missions->srv_recordResult(
            $mission,
            new RecordMissionResultDTO(
                result: MissionResult::Found,
                resultSummary: 'Le formulaire physique a été localisé.',
                searchedOn: CarbonImmutable::today(),
            ),
            $this->agent,
        );
    }

    private function recordNeedsVerification(SearchMissionModel $mission): SearchMissionModel
    {
        return $this->missions->srv_recordResult(
            $mission,
            new RecordMissionResultDTO(
                result: MissionResult::NeedsVerification,
                resultSummary: 'Le document doit être contrôlé par un responsable.',
                searchedOn: CarbonImmutable::today(),
            ),
            $this->agent,
        );
    }

    private function assign(UserModel $responsible, SearchLocationModel $location): SearchMissionModel
    {
        return $this->missions->srv_assign(
            $this->form->refresh(),
            new AssignSearchMissionDTO(
                searchLocationCodeUnique: $location->search_location_code_unique,
                responsibleUserCodeUnique: $responsible->user_code_unique,
                dueDate: CarbonImmutable::today()->addWeek(),
                priority: FormPriority::High,
            ),
            $this->manager,
        );
    }

    private function userForRole(RoleCode $roleCode): UserModel
    {
        $role = RoleModel::query()->where('role_code', $roleCode->value)->firstOrFail();

        return UserModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
        ]);
    }
}
