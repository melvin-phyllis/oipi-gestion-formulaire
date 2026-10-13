<?php

namespace App\Services\Analytics;

use App\Dto\Analytics\AnalyticsFiltersDTO;
use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Enums\Audit\AuditAction;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\UserModel;
use App\Models\Audit\AuditLogModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Administration\UserRepositoryInterface;
use App\Repositories\Interfaces\Analytics\AnalyticsRepositoryInterface;
use App\Repositories\Interfaces\Audit\AuditRepositoryInterface;
use App\Repositories\Interfaces\Registry\RegistryReferenceRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class AnalyticsService
{
    public function __construct(
        private readonly AnalyticsRepositoryInterface $analytics,
        private readonly RegistryReferenceRepositoryInterface $references,
        private readonly UserRepositoryInterface $users,
        private readonly AuditRepositoryInterface $audit,
    ) {}

    /** @return array<string, mixed> */
    public function srv_report(AnalyticsFiltersDTO $filters): array
    {
        $statusCounts = $this->statusCounts($filters);
        $missionCounts = $this->analytics->repo_missionCounts($filters);
        $kpis = $this->kpis($statusCounts, $missionCounts);

        return [
            'kpis' => $kpis,
            'statusDistribution' => $this->statusDistribution($statusCounts, $kpis['totalForms']),
            'titleTypeDistribution' => $this->analytics->repo_titleTypeCounts($filters)
                ->map(fn (object $row): array => [
                    'codeUnique' => $row->title_type_code_unique,
                    'code' => $row->title_type_code,
                    'label' => $row->title_type_name,
                    'total' => (int) $row->total,
                    'percentage' => $this->percentage((int) $row->total, $kpis['totalForms']),
                ])
                ->values(),
            'yearDistribution' => $this->analytics->repo_yearCounts($filters)
                ->map(fn (object $row): array => [
                    'year' => (int) $row->form_year,
                    'total' => (int) $row->total,
                ])
                ->values(),
            'locationPerformance' => $this->analytics->repo_locationPerformance($filters)
                ->map(fn (object $row): array => [
                    'codeUnique' => $row->search_location_code_unique,
                    'label' => $row->search_location_name ?: 'Localisation non renseignée',
                    'searches' => (int) $row->searches,
                    'found' => (int) $row->found,
                    'successRate' => $this->percentage((int) $row->found, (int) $row->searches),
                ])
                ->values(),
            'responsiblePerformance' => $this->analytics->repo_responsiblePerformance($filters)
                ->map(fn (object $row): array => [
                    'codeUnique' => $row->responsible_user_code_unique,
                    'label' => $row->responsible_name ?: 'Responsable non renseigné',
                    'searches' => (int) $row->searches,
                    'found' => (int) $row->found,
                    'notFound' => (int) $row->not_found,
                    'needsVerification' => (int) $row->needs_verification,
                    'successRate' => $this->percentage((int) $row->found, (int) $row->searches),
                ])
                ->values(),
            'monthlyTrend' => $this->monthlyTrend($filters),
            'titleTypes' => $this->references->repo_titleTypes(),
            'searchLocations' => $this->references->repo_searchLocations(),
            'responsibleUsers' => $this->users->repo_activeUsers(),
            'statuses' => FormStatus::cases(),
            'priorities' => FormPriority::cases(),
            'trendPeriods' => [6, 12, 24],
            'periodUsesRegistrationDate' => true,
        ];
    }

    /** @return array<string, mixed> */
    public function srv_dashboard(UserModel $viewer): array
    {
        $filters = new AnalyticsFiltersDTO(trendMonths: 6);
        $statusCounts = $this->statusCounts($filters);
        $missionCounts = $this->analytics->repo_missionCounts($filters);
        $kpis = $this->kpis($statusCounts, $missionCounts);

        $viewer->loadMissing('role');
        $role = RoleCode::tryFrom((string) $viewer->role?->role_code);
        $isPersonalScope = $role === RoleCode::SearchAgent;
        $canAssign = $viewer->can(PermissionCode::MissionsAssign->value);
        $canReview = $viewer->can(PermissionCode::MissionsReviewFound->value);
        $canFinalize = $viewer->can(PermissionCode::RegistryFinalize->value);
        $canViewMissionQueue = $canAssign
            || $viewer->can(PermissionCode::MissionsRecordAnyResult->value)
            || $viewer->can(PermissionCode::MissionsRecordOwnResult->value);
        $canViewActivity = $viewer->can(PermissionCode::AuditView->value);
        $hasOperationalScope = $isPersonalScope || $canAssign || $canReview || $canFinalize;
        $responsible = $isPersonalScope ? $viewer->user_code_unique : null;

        $attention = $this->analytics->repo_dashboardAttention(
            responsibleUserCodeUnique: $responsible,
            includeMissionQueue: $canViewMissionQueue,
            includeAssignmentQueue: $canAssign,
            includeReviewQueue: $canReview,
            includeFinalizationQueues: $canFinalize,
        );

        return [
            'kpis' => $kpis,
            'dashboardScope' => $isPersonalScope
                ? 'personal'
                : ($hasOperationalScope ? 'global' : 'overview'),
            'capabilities' => [
                'viewMissionQueue' => $canViewMissionQueue,
                'assignMissions' => $canAssign,
                'reviewFoundReports' => $canReview,
                'finalizeForms' => $canFinalize,
                'viewOperationalActivity' => $canViewActivity,
            ],
            'attentionCounts' => $attention['counts'],
            'attentionItems' => [
                'overdueMissions' => $attention['overdueMissions']
                    ->map(fn (SearchMissionModel $mission): array => $this->missionItem($mission))
                    ->values(),
                'dueSoonMissions' => $attention['dueSoonMissions']
                    ->map(fn (SearchMissionModel $mission): array => $this->missionItem($mission))
                    ->values(),
                'formsWithoutMission' => $this->formItems($attention['formsWithoutMission']),
                'pendingReviews' => $this->formItems($attention['pendingReviews']),
                'awaitingDigitization' => $this->formItems($attention['awaitingDigitization']),
                'awaitingEntry' => $this->formItems($attention['awaitingEntry']),
            ],
            'workflowProgress' => $this->workflowProgress(
                $statusCounts,
                $kpis['activeCampaignForms'],
            ),
            'monthlyTrend' => $this->monthlyTrend($filters),
            'priorityForms' => $hasOperationalScope
                ? $this->formItems($this->analytics->repo_priorityForms($responsible))
                : collect(),
            'recentActivity' => $canViewActivity
                ? $this->audit->repo_recentOperationalActivities()->map(
                    fn (AuditLogModel $activity): array => $this->activityItem($activity),
                )->values()
                : collect(),
        ];
    }

    /** @return Collection<string, int> */
    private function statusCounts(AnalyticsFiltersDTO $filters): Collection
    {
        return $this->analytics->repo_statusCounts($filters)
            ->mapWithKeys(function (object $row): array {
                $status = $row->status instanceof FormStatus
                    ? $row->status->value
                    : (string) $row->status;

                return [$status => (int) $row->total];
            });
    }

    /**
     * @param  Collection<string, int>  $statusCounts
     * @param  array{total: int, active: int, overdue: int, completed: int}  $missionCounts
     * @return array<string, int|float>
     */
    private function kpis(Collection $statusCounts, array $missionCounts): array
    {
        $total = $statusCounts->sum();
        $archived = $statusCounts->get(FormStatus::Archived->value, 0);
        $activeCampaign = $total - $archived;
        $resolved = collect([
            FormStatus::Found,
            FormStatus::Digitized,
            FormStatus::Entered,
        ])->sum(fn (FormStatus $status): int => $statusCounts->get($status->value, 0));
        $entered = $statusCounts->get(FormStatus::Entered->value, 0);

        return [
            'totalForms' => $total,
            'activeCampaignForms' => $activeCampaign,
            'archivedForms' => $archived,
            'resolvedForms' => $resolved,
            'enteredForms' => $entered,
            'resolutionRate' => $this->percentage($resolved, $activeCampaign),
            'completionRate' => $this->percentage($entered, $activeCampaign),
            'totalMissions' => $missionCounts['total'],
            'activeMissions' => $missionCounts['active'],
            'overdueMissions' => $missionCounts['overdue'],
            'completedMissions' => $missionCounts['completed'],
        ];
    }

    /** @param Collection<string, int> $statusCounts */
    private function statusDistribution(Collection $statusCounts, int $total): Collection
    {
        return collect(FormStatus::cases())->map(fn (FormStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
            'color' => $status->color(),
            'total' => $statusCounts->get($status->value, 0),
            'percentage' => $this->percentage($statusCounts->get($status->value, 0), $total),
        ]);
    }

    /** @return Collection<int, array<string, int|string>> */
    private function monthlyTrend(AnalyticsFiltersDTO $filters): Collection
    {
        $registrations = $this->analytics->repo_monthlyRegistrationCounts($filters)->keyBy('month');
        $searches = $this->analytics->repo_monthlySearchCounts($filters)->keyBy('month');
        $resolutions = $this->analytics->repo_monthlyResolutionCounts($filters)->keyBy('month');
        $firstMonth = CarbonImmutable::now()->startOfMonth()->subMonths($filters->trendMonths - 1);

        return collect(range(0, $filters->trendMonths - 1))->map(function (int $offset) use (
            $firstMonth,
            $registrations,
            $searches,
            $resolutions,
        ): array {
            $month = $firstMonth->addMonths($offset);
            $key = $month->format('Y-m');
            $registrationRow = $registrations->get($key);
            $searchRow = $searches->get($key);
            $resolutionRow = $resolutions->get($key);

            return [
                'month' => $key,
                'label' => ucfirst($month->locale('fr')->translatedFormat('M Y')),
                'registrations' => (int) ($registrationRow?->registrations ?? 0),
                'searches' => (int) ($searchRow?->searches ?? 0),
                'foundReports' => (int) ($searchRow?->found ?? 0),
                'resolvedForms' => (int) ($resolutionRow?->resolved ?? 0),
            ];
        });
    }

    /** @param Collection<string, int> $statusCounts */
    private function workflowProgress(Collection $statusCounts, int $activeCampaign): Collection
    {
        return collect(FormStatus::cases())
            ->reject(fn (FormStatus $status): bool => $status === FormStatus::Archived)
            ->map(fn (FormStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'order' => $status->order(),
                'total' => $statusCounts->get($status->value, 0),
                'percentage' => $this->percentage(
                    $statusCounts->get($status->value, 0),
                    $activeCampaign,
                ),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, MissingFormModel>  $forms
     * @return Collection<int, array<string, mixed>>
     */
    private function formItems(Collection $forms): Collection
    {
        return $forms
            ->map(fn (MissingFormModel $form): array => $this->formItem($form))
            ->values();
    }

    /** @return array<string, mixed> */
    private function formItem(MissingFormModel $form): array
    {
        $status = $form->status instanceof FormStatus
            ? $form->status
            : FormStatus::from((string) $form->status);
        $priority = $form->priority instanceof FormPriority
            ? $form->priority
            : FormPriority::from((string) $form->priority);

        return [
            'uuid' => $form->uuid,
            'codeUnique' => $form->missing_form_code_unique,
            'registryNumber' => $form->registry_number,
            'formNumber' => $form->form_number,
            'formYear' => $form->form_year,
            'titleType' => $form->titleType?->title_type_name,
            'status' => $status->value,
            'statusLabel' => $status->label(),
            'priority' => $priority->value,
            'priorityLabel' => $priority->label(),
            'responsibleCodeUnique' => $form->last_responsible_user_code_unique,
            'responsibleName' => $this->userName($form->lastResponsible),
            'searchLocation' => $form->lastSearchLocation?->location_name,
            'dueDate' => $form->search_due_date?->toDateString(),
        ];
    }

    /** @return array<string, mixed> */
    private function missionItem(SearchMissionModel $mission): array
    {
        $status = $mission->status instanceof MissionStatus
            ? $mission->status
            : MissionStatus::from((string) $mission->status);
        $priority = $mission->priority instanceof FormPriority
            ? $mission->priority
            : FormPriority::from((string) $mission->priority);

        return [
            'uuid' => $mission->uuid,
            'codeUnique' => $mission->search_mission_code_unique,
            'missingFormUuid' => $mission->missingForm?->uuid,
            'registryNumber' => $mission->missingForm?->registry_number,
            'formNumber' => $mission->missingForm?->form_number,
            'titleType' => $mission->missingForm?->titleType?->title_type_name,
            'status' => $status->value,
            'statusLabel' => $this->missionStatusLabel($status),
            'priority' => $priority->value,
            'priorityLabel' => $priority->label(),
            'responsibleCodeUnique' => $mission->responsible_user_code_unique,
            'responsibleName' => $this->userName($mission->responsible),
            'searchLocation' => $mission->searchLocation?->location_name,
            'dueDate' => $mission->due_date?->toDateString(),
            'isOverdue' => $mission->due_date?->isBefore(today()) ?? false,
        ];
    }

    /** @return array<string, mixed> */
    private function activityItem(AuditLogModel $activity): array
    {
        $action = AuditAction::tryFrom($activity->action_code);

        return [
            'uuid' => $activity->uuid,
            'actionCode' => $activity->action_code,
            'actionLabel' => $action?->label() ?? $activity->action_code,
            'description' => $activity->description,
            'actorName' => $activity->actor_name,
            'subjectType' => $activity->subject_type,
            'subjectUuid' => $activity->subject_uuid,
            'subjectCodeUnique' => $activity->subject_code_unique,
            'subjectLabel' => $activity->subject_label,
            'occurredAt' => $activity->occurred_at?->toAtomString(),
        ];
    }

    private function userName(?UserModel $user): ?string
    {
        if (! $user instanceof UserModel) {
            return null;
        }

        return trim("{$user->last_name} {$user->first_names}");
    }

    private function missionStatusLabel(MissionStatus $status): string
    {
        return match ($status) {
            MissionStatus::Assigned => 'Affectée',
            MissionStatus::InProgress => 'En cours',
            MissionStatus::Completed => 'Terminée',
            MissionStatus::Cancelled => 'Annulée',
        };
    }

    private function percentage(int $value, int $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0.0;
    }
}
