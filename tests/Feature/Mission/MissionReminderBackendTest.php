<?php

namespace Tests\Feature\Mission;

use App\Dto\Mission\MissionReminderCandidateDTO;
use App\Enums\Administration\RoleCode;
use App\Enums\Mission\MissionReminderType;
use App\Enums\Mission\MissionStatus;
use App\Events\Mission\MissionReminderSent;
use App\Jobs\Mission\SendMissionReminder;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchMissionModel;
use App\Notifications\Mission\MissionReminderNotification;
use App\Services\Mission\MissionReminderService;
use Carbon\CarbonImmutable;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class MissionReminderBackendTest extends TestCase
{
    use RefreshDatabase;

    private MissionReminderService $service;

    private UserModel $responsible;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-12 06:00:00');
        config([
            'risfm.mission_reminders.before_due_days' => 2,
            'risfm.mission_reminders.escalation_after_days' => 3,
            'risfm.mission_reminders.mail_enabled' => false,
        ]);
        $this->service = app(MissionReminderService::class);
        $this->responsible = UserModel::factory()->create();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_scheduler_dispatches_each_expected_reminder_category(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $administratorRole = RoleModel::query()
            ->where('role_code', RoleCode::SystemAdmin->value)
            ->firstOrFail();
        $administrator = UserModel::factory()->create([
            'role_code_unique' => $administratorRole->role_code_unique,
        ]);

        $this->missionDueOn('2026-10-14');
        $this->missionDueOn('2026-10-12');
        $this->missionDueOn('2026-10-10');
        $escalated = $this->missionDueOn('2026-10-08');
        Bus::fake();

        $count = $this->service->srv_dispatchScheduled(CarbonImmutable::parse('2026-10-12'));

        $this->assertSame(5, $count);
        Bus::assertDispatched(SendMissionReminder::class, 5);
        Bus::assertDispatched(fn (SendMissionReminder $job): bool => $job->type === MissionReminderType::BeforeDue && $job->daysDelta === 2);
        Bus::assertDispatched(fn (SendMissionReminder $job): bool => $job->type === MissionReminderType::DueToday && $job->daysDelta === 0);
        Bus::assertDispatched(fn (SendMissionReminder $job): bool => $job->type === MissionReminderType::Overdue && $job->daysDelta === -2);
        Bus::assertDispatched(fn (SendMissionReminder $job): bool => $job->type === MissionReminderType::Escalation
            && $job->missionCodeUnique === $escalated->search_mission_code_unique
            && $job->recipientUserCodeUnique === $administrator->user_code_unique);
    }

    public function test_sending_the_same_reminder_twice_is_idempotent(): void
    {
        Event::fake([MissionReminderSent::class]);
        $mission = $this->missionDueOn('2026-10-12');
        $candidate = new MissionReminderCandidateDTO(
            missionCodeUnique: $mission->search_mission_code_unique,
            recipientUserCodeUnique: $this->responsible->user_code_unique,
            type: MissionReminderType::DueToday,
            reminderDate: CarbonImmutable::parse('2026-10-12'),
            daysDelta: 0,
        );

        $first = $this->service->srv_send($candidate);
        $second = $this->service->srv_send($candidate);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->uuid, $second->uuid);
        $this->assertDatabaseCount('mis_mission_reminder_models', 1);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame($first->uuid, $first->notification_id);
        $this->assertNotNull($first->database_sent_at);
        Event::assertDispatchedTimes(MissionReminderSent::class, 1);
    }

    public function test_closed_mission_is_ignored_when_a_queued_reminder_runs_late(): void
    {
        $mission = $this->missionDueOn('2026-10-12');
        $mission->update(['status' => MissionStatus::Completed]);

        $result = $this->service->srv_send(new MissionReminderCandidateDTO(
            missionCodeUnique: $mission->search_mission_code_unique,
            recipientUserCodeUnique: $this->responsible->user_code_unique,
            type: MissionReminderType::DueToday,
            reminderDate: CarbonImmutable::parse('2026-10-12'),
            daysDelta: 0,
        ));

        $this->assertNull($result);
        $this->assertDatabaseCount('mis_mission_reminder_models', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_old_responsible_is_ignored_when_mission_was_reassigned_after_dispatch(): void
    {
        $mission = $this->missionDueOn('2026-10-12');
        $newResponsible = UserModel::factory()->create();
        $mission->update(['responsible_user_code_unique' => $newResponsible->user_code_unique]);

        $result = $this->service->srv_send(new MissionReminderCandidateDTO(
            missionCodeUnique: $mission->search_mission_code_unique,
            recipientUserCodeUnique: $this->responsible->user_code_unique,
            type: MissionReminderType::DueToday,
            reminderDate: CarbonImmutable::parse('2026-10-12'),
            daysDelta: 0,
        ));

        $this->assertNull($result);
        $this->assertDatabaseCount('mis_mission_reminder_models', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_job_has_a_stable_unique_identifier(): void
    {
        $job = new SendMissionReminder(
            missionCodeUnique: 'MSN-TEST',
            recipientUserCodeUnique: 'USR-TEST',
            type: MissionReminderType::Overdue,
            reminderDate: '2026-10-12',
            daysDelta: -1,
        );

        $this->assertSame(
            'MSN-TEST|USR-TEST|overdue|2026-10-12',
            $job->uniqueId(),
        );
        $this->assertSame('notifications', $job->queue);
    }

    public function test_reminder_notification_mail_renders_properly(): void
    {
        $notification = new MissionReminderNotification(
            reminderType: MissionReminderType::DueToday,
            missionUuid: '00000000-0000-0000-0000-000000000001',
            missingFormUuid: '00000000-0000-0000-0000-000000000002',
            registryNumber: 'FM-2026-000088',
            dueDate: CarbonImmutable::parse('2026-10-14'),
            daysDelta: 0,
        );

        $html = (string) $notification->toMail($this->responsible)->render();

        $this->assertStringContainsString('FM-2026-000088', $html);
        $this->assertStringContainsString('14/10/2026', $html);
        $this->assertStringContainsString('Office Ivoirien de la Propriété Intellectuelle (OIPI)', $html);
        $this->assertStringContainsString('Consulter et traiter la mission', $html);
        $this->assertStringContainsString(route('missions.show', '00000000-0000-0000-0000-000000000001'), $html);
    }

    private function missionDueOn(string $dueDate): SearchMissionModel
    {
        return SearchMissionModel::factory()->create([
            'responsible_user_code_unique' => $this->responsible->user_code_unique,
            'status' => MissionStatus::Assigned,
            'due_date' => $dueDate,
        ]);
    }
}
