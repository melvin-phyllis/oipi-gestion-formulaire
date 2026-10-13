<?php

namespace Tests\Feature\Registry;

use App\Dto\Mission\AssignSearchMissionDTO;
use App\Dto\Registry\ReopenMissingFormDTO;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Events\Registry\MissingFormReopened;
use App\Models\Administration\UserModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Services\Mission\SearchMissionService;
use App\Services\Registry\FormReopeningService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use LogicException;
use Tests\TestCase;

final class FormReopeningBackendTest extends TestCase
{
    use RefreshDatabase;

    private FormReopeningService $service;

    private UserModel $actor;

    private MissingFormModel $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FormReopeningService::class);
        $this->actor = UserModel::factory()->create();
        $this->form = MissingFormModel::factory()->create([
            'status' => FormStatus::Entered,
            'tracking_cycle' => 1,
            'last_search_location_code_unique' => SearchLocationModel::factory()->create()->search_location_code_unique,
            'last_responsible_user_code_unique' => $this->actor->user_code_unique,
            'search_due_date' => '2026-10-20',
            'last_search_date' => '2026-10-10',
            'resolved_at' => '2026-10-10 09:00:00',
            'result_summary' => 'Formulaire retrouvé et traité.',
        ]);
    }

    public function test_reopening_starts_a_clean_cycle_without_erasing_first_resolution(): void
    {
        Event::fake([MissingFormReopened::class]);
        $resolvedAt = $this->form->resolved_at;

        $record = $this->service->srv_reopen(
            $this->form,
            new ReopenMissingFormDTO('Le document saisi ne correspond pas au titre attendu.'),
            $this->actor,
        );

        $this->assertSame(1, $record->previous_tracking_cycle);
        $this->assertSame(2, $record->new_tracking_cycle);
        $this->assertSame(FormStatus::Entered, $record->previous_status);
        $this->assertTrue($record->reopenedBy->is($this->actor));

        $this->form->refresh();
        $this->assertSame(2, $this->form->tracking_cycle);
        $this->assertSame(FormStatus::Missing, $this->form->status);
        $this->assertNull($this->form->last_search_location_code_unique);
        $this->assertNull($this->form->last_responsible_user_code_unique);
        $this->assertNull($this->form->search_due_date);
        $this->assertNull($this->form->last_search_date);
        $this->assertNull($this->form->result_summary);
        $this->assertTrue($this->form->resolved_at->equalTo($resolvedAt));
        Event::assertDispatched(MissingFormReopened::class);
    }

    public function test_new_mission_after_reopening_belongs_to_the_new_cycle(): void
    {
        $this->service->srv_reopen(
            $this->form,
            new ReopenMissingFormDTO('Une nouvelle recherche physique est nécessaire.'),
            $this->actor,
        );
        $responsible = UserModel::factory()->create();
        $location = SearchLocationModel::factory()->create();

        $mission = app(SearchMissionService::class)->srv_assign(
            $this->form->refresh(),
            new AssignSearchMissionDTO(
                searchLocationCodeUnique: $location->search_location_code_unique,
                responsibleUserCodeUnique: $responsible->user_code_unique,
                dueDate: CarbonImmutable::parse('2026-10-25'),
                priority: FormPriority::High,
            ),
            $this->actor,
        );

        $this->assertSame(2, $mission->tracking_cycle);
        $this->assertSame(FormStatus::InSearch, $this->form->refresh()->status);
    }

    public function test_unresolved_form_cannot_be_reopened(): void
    {
        $this->form->update(['status' => FormStatus::InSearch]);

        $this->expectException(LogicException::class);
        $this->service->srv_reopen(
            $this->form->refresh(),
            new ReopenMissingFormDTO('Tentative de réouverture invalide.'),
            $this->actor,
        );
    }
}
