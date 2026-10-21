<?php

namespace Tests\Feature\Mission;

use App\Dto\Mission\AssignSearchMissionDTO;
use App\Dto\Mission\CancelSearchMissionDTO;
use App\Dto\Mission\ReassignSearchMissionDTO;
use App\Dto\Mission\RecordMissionResultDTO;
use App\Dto\Mission\StartSearchMissionDTO;
use App\Dto\Registry\ArchiveMissingFormDTO;
use App\Enums\Mission\MissionResult;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Events\Mission\SearchMissionAssigned;
use App\Events\Mission\SearchMissionCancelled;
use App\Events\Mission\SearchMissionCompleted;
use App\Events\Mission\SearchMissionReassigned;
use App\Events\Mission\SearchMissionStarted;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchHistoryModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Services\Mission\SearchMissionService;
use App\Services\Registry\MissingFormService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

final class SearchMissionBackendTest extends TestCase
{
    use RefreshDatabase;

    private SearchMissionService $service;

    private UserModel $assigner;

    private UserModel $responsible;

    private SearchLocationModel $location;

    private MissingFormModel $form;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-15 08:00:00');

        $this->service = app(SearchMissionService::class);
        $this->assigner = UserModel::factory()->create();
        $this->responsible = UserModel::factory()->create();
        $this->location = SearchLocationModel::factory()->create();
        $this->form = MissingFormModel::factory()->create();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_assignment_updates_the_form_and_prevents_an_active_duplicate(): void
    {
        Event::fake([SearchMissionAssigned::class]);

        $mission = $this->assign($this->form, $this->responsible, $this->location);

        $this->assertSame(MissionStatus::Assigned, $mission->status);
        $this->assertStringStartsWith('MSN-', $mission->search_mission_code_unique);
        $this->assertSame($this->form->tracking_cycle, $mission->tracking_cycle);
        $this->assertSame(FormPriority::High, $mission->priority);
        $this->form->refresh();
        $this->assertSame(FormStatus::InSearch, $this->form->status);
        $this->assertSame($this->responsible->user_code_unique, $this->form->last_responsible_user_code_unique);
        $this->assertSame($this->location->search_location_code_unique, $this->form->last_search_location_code_unique);
        Event::assertDispatched(SearchMissionAssigned::class);

        $this->expectException(ValidationException::class);
        $this->assign($this->form, $this->responsible, $this->location);
    }

    public function test_found_result_waits_for_review_without_cancelling_parallel_work(): void
    {
        Event::fake([SearchMissionCompleted::class]);
        $mission = $this->assign($this->form, $this->responsible, $this->location);
        $otherResponsible = UserModel::factory()->create();
        $otherLocation = SearchLocationModel::factory()->create();
        $parallelMission = $this->assign($this->form, $otherResponsible, $otherLocation);

        $completed = $this->service->srv_recordResult(
            $mission,
            new RecordMissionResultDTO(
                result: MissionResult::Found,
                resultSummary: 'Formulaire retrouvé dans le rayon B4.',
                searchedOn: CarbonImmutable::today(),
            ),
            $this->responsible,
        );

        $this->assertSame(MissionStatus::Completed, $completed->status);
        $this->assertSame(MissionResult::Found, $completed->result);
        $this->assertNull($completed->active_assignment_key);
        $this->assertSame(MissionStatus::Assigned, $parallelMission->refresh()->status);
        $this->assertNull($parallelMission->cancellation_reason);

        $this->form->refresh();
        $this->assertSame(FormStatus::NeedsVerification, $this->form->status);
        $this->assertNull($this->form->resolved_at);
        $this->assertNotNull($this->form->search_due_date);
        $this->assertSame('Formulaire retrouvé dans le rayon B4.', $this->form->result_summary);

        $history = SearchHistoryModel::query()->sole();
        $this->assertSame($mission->search_mission_code_unique, $history->search_mission_code_unique);
        $this->assertSame(FormStatus::NeedsVerification, $history->form_status);
        $this->assertSame(MissionResult::Found, $history->mission_result);
        $this->assertSame(trim($this->responsible->last_name.' '.$this->responsible->first_names), $history->responsible_name);
        Event::assertDispatched(SearchMissionCompleted::class);
    }

    public function test_unsuccessful_result_keeps_form_in_search_while_another_mission_is_active(): void
    {
        $mission = $this->assign($this->form, $this->responsible, $this->location);
        $this->assign(
            $this->form,
            UserModel::factory()->create(),
            SearchLocationModel::factory()->create(),
        );

        $this->service->srv_recordResult(
            $mission,
            new RecordMissionResultDTO(
                result: MissionResult::NotFound,
                resultSummary: 'Aucun formulaire dans cette salle.',
                searchedOn: CarbonImmutable::today(),
            ),
            $this->responsible,
        );

        $this->assertSame(FormStatus::InSearch, $this->form->refresh()->status);
        $this->assertSame(1, SearchMissionModel::query()->where('status', MissionStatus::Assigned)->count());
    }

    public function test_verification_result_sets_the_expected_status_when_no_other_mission_is_active(): void
    {
        $mission = $this->assign($this->form, $this->responsible, $this->location);

        $this->service->srv_recordResult(
            $mission,
            new RecordMissionResultDTO(
                result: MissionResult::NeedsVerification,
                resultSummary: 'Une copie non identifiée doit être vérifiée.',
                searchedOn: CarbonImmutable::today(),
            ),
            $this->responsible,
        );

        $this->assertSame(FormStatus::NeedsVerification, $this->form->refresh()->status);
    }

    public function test_a_form_with_an_active_mission_cannot_be_archived(): void
    {
        $this->assign($this->form, $this->responsible, $this->location);

        $this->expectException(LogicException::class);
        app(MissingFormService::class)->srv_archive(
            $this->form->refresh(),
            new ArchiveMissingFormDTO('Archivage demandé pendant une recherche.'),
            $this->assigner,
        );
    }

    public function test_responsible_can_start_an_assigned_mission(): void
    {
        Event::fake([SearchMissionStarted::class]);
        $mission = $this->assign($this->form, $this->responsible, $this->location);

        $started = $this->service->srv_start(
            $mission,
            StartSearchMissionDTO::fromValidated([]),
            $this->responsible,
        );

        $this->assertSame(MissionStatus::InProgress, $started->status);
        Event::assertDispatched(SearchMissionStarted::class);
    }

    public function test_manual_cancellation_closes_mission_and_resets_form_without_parallel_work(): void
    {
        Event::fake([SearchMissionCancelled::class]);
        $mission = $this->assign($this->form, $this->responsible, $this->location);

        $cancelled = $this->service->srv_cancel(
            $mission,
            new CancelSearchMissionDTO('Le rayon est temporairement inaccessible.'),
            $this->assigner,
        );

        $this->assertSame(MissionStatus::Cancelled, $cancelled->status);
        $this->assertSame('Le rayon est temporairement inaccessible.', $cancelled->cancellation_reason);
        $this->assertNull($cancelled->active_assignment_key);
        $this->form->refresh();
        $this->assertSame(FormStatus::Missing, $this->form->status);
        $this->assertNull($this->form->last_responsible_user_code_unique);
        $this->assertNull($this->form->last_search_location_code_unique);
        $this->assertNull($this->form->search_due_date);
        Event::assertDispatched(SearchMissionCancelled::class);
    }

    public function test_cancelling_one_parallel_mission_keeps_the_other_assignment_active(): void
    {
        $first = $this->assign($this->form, $this->responsible, $this->location);
        $otherResponsible = UserModel::factory()->create();
        $otherLocation = SearchLocationModel::factory()->create();
        $other = $this->assign($this->form, $otherResponsible, $otherLocation);

        $this->service->srv_cancel(
            $first,
            new CancelSearchMissionDTO('Réorganisation de la campagne.'),
            $this->assigner,
        );

        $this->form->refresh();
        $this->assertSame(FormStatus::InSearch, $this->form->status);
        $this->assertSame($otherResponsible->user_code_unique, $this->form->last_responsible_user_code_unique);
        $this->assertSame($otherLocation->search_location_code_unique, $this->form->last_search_location_code_unique);
        $this->assertSame(MissionStatus::Assigned, $other->refresh()->status);
    }

    public function test_reassignment_cancels_previous_mission_and_links_the_replacement(): void
    {
        Event::fake([SearchMissionReassigned::class]);
        $previous = $this->assign($this->form, $this->responsible, $this->location);
        $newResponsible = UserModel::factory()->create();
        $newLocation = SearchLocationModel::factory()->create();

        $replacement = $this->service->srv_reassign(
            $previous,
            new ReassignSearchMissionDTO(
                searchLocationCodeUnique: $newLocation->search_location_code_unique,
                responsibleUserCodeUnique: $newResponsible->user_code_unique,
                dueDate: CarbonImmutable::parse('2026-10-25'),
                priority: FormPriority::Urgent,
                reason: 'Le premier agent est indisponible.',
            ),
            $this->assigner,
        );

        $this->assertSame(MissionStatus::Cancelled, $previous->refresh()->status);
        $this->assertSame('Réaffectation : Le premier agent est indisponible.', $previous->cancellation_reason);
        $this->assertSame($previous->search_mission_code_unique, $replacement->parent_mission_code_unique);
        $this->assertSame(MissionStatus::Assigned, $replacement->status);
        $this->assertSame(FormPriority::Urgent, $replacement->priority);
        $this->assertTrue($replacement->parentMission->is($previous));
        $this->form->refresh();
        $this->assertSame($newResponsible->user_code_unique, $this->form->last_responsible_user_code_unique);
        $this->assertSame($newLocation->search_location_code_unique, $this->form->last_search_location_code_unique);
        Event::assertDispatched(SearchMissionReassigned::class);
    }

    private function assign(
        MissingFormModel $form,
        UserModel $responsible,
        SearchLocationModel $location,
    ): SearchMissionModel {
        return $this->service->srv_assign(
            $form,
            new AssignSearchMissionDTO(
                searchLocationCodeUnique: $location->search_location_code_unique,
                responsibleUserCodeUnique: $responsible->user_code_unique,
                dueDate: CarbonImmutable::parse('2026-10-20'),
                priority: FormPriority::High,
            ),
            $this->assigner,
        );
    }
}
