<?php

namespace Tests\Feature\Registry;

use App\Dto\Registry\ConfirmFinalizationDTO;
use App\Enums\Registry\FinalizationStep;
use App\Enums\Registry\FormStatus;
use App\Events\Registry\FormFinalizationStepConfirmed;
use App\Models\Administration\UserModel;
use App\Models\Registry\FormFinalizationModel;
use App\Models\Registry\MissingFormModel;
use App\Services\Registry\FormFinalizationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

final class FormFinalizationBackendTest extends TestCase
{
    use RefreshDatabase;

    private FormFinalizationService $service;

    private UserModel $actor;

    private MissingFormModel $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FormFinalizationService::class);
        $this->actor = UserModel::factory()->create();
        $this->form = MissingFormModel::factory()->create([
            'status' => FormStatus::Found,
            'resolved_at' => CarbonImmutable::parse('2026-10-10 09:30:00'),
        ]);
    }

    public function test_digitized_then_entered_is_the_only_valid_finalization_order(): void
    {
        Event::fake([FormFinalizationStepConfirmed::class]);
        $resolvedAt = $this->form->resolved_at;

        $digitized = $this->service->srv_confirmDigitized(
            $this->form,
            new ConfirmFinalizationDTO(CarbonImmutable::parse('2026-10-11 08:00:00')),
            $this->actor,
        );

        $this->assertSame(FinalizationStep::Digitized, $digitized->step);
        $this->assertSame(FormStatus::Digitized, $this->form->refresh()->status);
        $this->assertTrue($digitized->performedBy->is($this->actor));
        $this->assertSame(trim($this->actor->last_name.' '.$this->actor->first_names), $digitized->performed_by_name);

        $entered = $this->service->srv_confirmEntered(
            $this->form,
            new ConfirmFinalizationDTO(CarbonImmutable::parse('2026-10-12 08:00:00')),
            $this->actor,
        );

        $this->assertSame(FinalizationStep::Entered, $entered->step);
        $this->assertSame(FormStatus::Entered, $this->form->refresh()->status);
        $this->assertTrue($this->form->resolved_at->equalTo($resolvedAt));
        $this->assertCount(2, $this->form->finalizations);
        Event::assertDispatchedTimes(FormFinalizationStepConfirmed::class, 2);
    }

    public function test_entered_cannot_be_confirmed_before_digitized(): void
    {
        $this->expectException(LogicException::class);

        $this->service->srv_confirmEntered(
            $this->form,
            new ConfirmFinalizationDTO(CarbonImmutable::parse('2026-10-12 08:00:00')),
            $this->actor,
        );
    }

    public function test_step_date_cannot_precede_the_found_date(): void
    {
        try {
            $this->service->srv_confirmDigitized(
                $this->form,
                new ConfirmFinalizationDTO(CarbonImmutable::parse('2026-10-09 08:00:00')),
                $this->actor,
            );
            $this->fail('The invalid finalization date should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('performed_on', $exception->errors());
        }

        $this->assertDatabaseCount('reg_form_finalization_models', 0);
        $this->assertSame(FormStatus::Found, $this->form->refresh()->status);
    }

    public function test_a_step_already_recorded_for_the_cycle_cannot_be_recreated(): void
    {
        FormFinalizationModel::factory()->create([
            'missing_form_code_unique' => $this->form->missing_form_code_unique,
            'tracking_cycle' => $this->form->tracking_cycle,
            'step' => FinalizationStep::Digitized,
        ]);

        $this->expectException(LogicException::class);
        $this->service->srv_confirmDigitized(
            $this->form,
            new ConfirmFinalizationDTO(CarbonImmutable::parse('2026-10-12 08:00:00')),
            $this->actor,
        );
    }
}
