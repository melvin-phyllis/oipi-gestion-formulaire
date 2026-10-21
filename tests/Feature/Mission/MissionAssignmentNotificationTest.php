<?php

namespace Tests\Feature\Mission;

use App\Dto\Mission\AssignSearchMissionDTO;
use App\Dto\Mission\CancelSearchMissionDTO;
use App\Dto\Mission\ReassignSearchMissionDTO;
use App\Enums\Mission\MissionAssignmentNotificationType;
use App\Enums\Registry\FormPriority;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Notifications\Mission\MissionAssignmentNotification;
use App\Services\Mission\SearchMissionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class MissionAssignmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    private SearchMissionService $service;

    private UserModel $assigner;

    private UserModel $responsible;

    private MissingFormModel $form;

    private SearchLocationModel $location;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-15 08:00:00');

        $this->service = app(SearchMissionService::class);
        $this->assigner = UserModel::factory()->create([
            'last_name' => 'KOUASSI',
            'first_names' => 'Landry',
        ]);
        $this->responsible = UserModel::factory()->create([
            'last_name' => 'KONE',
            'first_names' => 'Awa',
        ]);
        $this->form = MissingFormModel::factory()->create([
            'registry_number' => 'FM-2026-000042',
            'form_number' => 'B-2026-000042',
        ]);
        $this->location = SearchLocationModel::factory()->create([
            'location_name' => 'Archives centrales',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_assignment_queues_database_and_mail_notification_for_responsible(): void
    {
        Notification::fake();

        $mission = $this->assign($this->responsible, $this->location);

        Notification::assertSentTo(
            $this->responsible,
            MissionAssignmentNotification::class,
            function (MissionAssignmentNotification $notification, array $channels) use ($mission): bool {
                return $notification->notificationType === MissionAssignmentNotificationType::Assigned
                    && $notification->missionUuid === $mission->uuid
                    && $notification->registryNumber === 'FM-2026-000042'
                    && $notification->searchLocation === 'Archives centrales'
                    && $notification->assignedBy === 'KOUASSI Landry'
                    && $channels === ['database', 'mail'];
            },
        );
    }

    public function test_assignment_persists_internal_notification_when_queue_is_processed(): void
    {
        $mission = $this->assign($this->responsible, $this->location);

        $notification = $this->responsible->notifications()->sole();

        $this->assertSame('mission_assignment', $notification->type);
        $this->assertSame('Nouvelle mission affectée', $notification->data['title']);
        $this->assertSame($mission->uuid, $notification->data['mission_uuid']);
        $this->assertSame($this->form->uuid, $notification->data['missing_form_uuid']);
        $this->assertSame('FM-2026-000042', $notification->data['registry_number']);
        $this->assertNull($notification->read_at);
    }

    public function test_cancellation_notifies_the_responsible_with_reason(): void
    {
        Notification::fake();
        $mission = $this->assign($this->responsible, $this->location);
        Notification::fake();

        $this->service->srv_cancel(
            $mission,
            new CancelSearchMissionDTO('Le rayon est temporairement inaccessible.'),
            $this->assigner,
        );

        Notification::assertSentTo(
            $this->responsible,
            MissionAssignmentNotification::class,
            fn (MissionAssignmentNotification $notification): bool => $notification->notificationType === MissionAssignmentNotificationType::Cancelled
                && $notification->reason === 'Le rayon est temporairement inaccessible.'
                && $notification->assignedBy === 'KOUASSI Landry',
        );
    }

    public function test_reassignment_notifies_previous_and_new_responsibles_differently(): void
    {
        Notification::fake();
        $previousMission = $this->assign($this->responsible, $this->location);
        $newResponsible = UserModel::factory()->create();
        $newLocation = SearchLocationModel::factory()->create([
            'location_name' => 'Direction technique',
        ]);
        Notification::fake();

        $replacement = $this->service->srv_reassign(
            $previousMission,
            new ReassignSearchMissionDTO(
                searchLocationCodeUnique: $newLocation->search_location_code_unique,
                responsibleUserCodeUnique: $newResponsible->user_code_unique,
                dueDate: CarbonImmutable::parse('2026-10-25'),
                priority: FormPriority::Urgent,
                reason: 'Le premier agent est indisponible.',
            ),
            $this->assigner,
        );

        Notification::assertSentTo(
            $this->responsible,
            MissionAssignmentNotification::class,
            fn (MissionAssignmentNotification $notification): bool => $notification->notificationType === MissionAssignmentNotificationType::Withdrawn
                && $notification->missionUuid === $previousMission->uuid
                && $notification->reason === 'Le premier agent est indisponible.',
        );
        Notification::assertSentTo(
            $newResponsible,
            MissionAssignmentNotification::class,
            fn (MissionAssignmentNotification $notification): bool => $notification->notificationType === MissionAssignmentNotificationType::Reassigned
                && $notification->missionUuid === $replacement->uuid
                && $notification->searchLocation === 'Direction technique'
                && $notification->reason === 'Le premier agent est indisponible.',
        );
        Notification::assertSentTimes(MissionAssignmentNotification::class, 2);
    }

    public function test_mail_templates_render_institutional_french_content_for_all_notification_types(): void
    {
        $mission = $this->assign($this->responsible, $this->location);

        foreach (MissionAssignmentNotificationType::cases() as $type) {
            $reason = in_array($type, [
                MissionAssignmentNotificationType::Reassigned,
                MissionAssignmentNotificationType::Withdrawn,
                MissionAssignmentNotificationType::Cancelled,
            ], true)
                ? 'Motif de suivi opérationnel'
                : null;

            $notification = new MissionAssignmentNotification(
                notificationType: $type,
                missionUuid: $mission->uuid,
                missingFormUuid: $this->form->uuid,
                registryNumber: 'FM-2026-000042',
                formNumber: 'B-2026-000042',
                searchLocation: 'Archives centrales',
                priority: FormPriority::High->label(),
                dueDate: CarbonImmutable::parse('2026-10-20'),
                assignedBy: 'KOUASSI Landry',
                reason: $reason,
            );

            $html = (string) $notification->toMail($this->responsible)->render();

            $this->assertStringContainsString('Bonjour Awa,', $html);
            $this->assertStringContainsString('FM-2026-000042', $html);
            $this->assertStringContainsString('B-2026-000042', $html);
            $this->assertStringContainsString('Archives centrales', $html);
            $this->assertStringContainsString(FormPriority::High->label(), $html);
            $this->assertStringContainsString('20/10/2026', $html);
            $this->assertStringContainsString('KOUASSI Landry', $html);
            $this->assertStringContainsString(route('missions.show', $mission->uuid), $html);
            $this->assertStringContainsString('alt="Office Ivoirien de la Propriété Intellectuelle (OIPI)"', $html);
            $this->assertStringContainsString('Registre Informatisé de Suivi des Formulaires Manquants (RISFM)', $html);
            $this->assertStringContainsString('background-color: #009846', $html);
            $this->assertStringContainsString('https://www.facebook.com/oipiofficiel/?locale=fr_FR', $html);
            $this->assertStringContainsString('https://ci.linkedin.com/company/oipi-office-ivoirien-de-la-propri%C3%A9t%C3%A9-intellectuelle', $html);
            $this->assertStringNotContainsString('Regards,', $html);
            $this->assertStringNotContainsString("If you're having trouble clicking", $html);

            if ($reason !== null) {
                $this->assertStringContainsString($reason, $html);
            }
        }
    }

    private function assign(
        UserModel $responsible,
        SearchLocationModel $location,
    ): SearchMissionModel {
        return $this->service->srv_assign(
            $this->form,
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
