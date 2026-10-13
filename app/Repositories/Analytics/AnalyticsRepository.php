<?php

namespace App\Repositories\Analytics;

use App\Dto\Analytics\AnalyticsFiltersDTO;
use App\Enums\Mission\MissionResult;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Models\Mission\SearchHistoryModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Analytics\AnalyticsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    public function repo_statusCounts(AnalyticsFiltersDTO $filters): Collection
    {
        return $this->applyFormFilters(MissingFormModel::query(), $filters)
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->get();
    }

    public function repo_missionCounts(AnalyticsFiltersDTO $filters): array
    {
        $row = $this->applyFormFilters(
            SearchMissionModel::query()
                ->join(
                    'reg_missing_form_models as analytics_forms',
                    'analytics_forms.missing_form_code_unique',
                    '=',
                    'mis_search_mission_models.missing_form_code_unique',
                )
                ->whereNull('analytics_forms.deleted_at'),
            $filters,
            'analytics_forms',
            false,
        )
            ->when(
                $filters->responsibleUserCodeUnique,
                fn (Builder $builder, string $responsible): Builder => $builder->where(
                    'mis_search_mission_models.responsible_user_code_unique',
                    $responsible,
                ),
            )
            ->when(
                $filters->searchLocationCodeUnique,
                fn (Builder $builder, string $location): Builder => $builder->where(
                    'mis_search_mission_models.search_location_code_unique',
                    $location,
                ),
            )
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN mis_search_mission_models.status IN (?, ?) THEN 1 ELSE 0 END) as active',
                [MissionStatus::Assigned->value, MissionStatus::InProgress->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN mis_search_mission_models.status IN (?, ?) AND mis_search_mission_models.due_date < ? THEN 1 ELSE 0 END) as overdue',
                [MissionStatus::Assigned->value, MissionStatus::InProgress->value, today()->toDateString()],
            )
            ->selectRaw(
                'SUM(CASE WHEN mis_search_mission_models.status = ? THEN 1 ELSE 0 END) as completed',
                [MissionStatus::Completed->value],
            )
            ->first();

        return [
            'total' => (int) ($row?->total ?? 0),
            'active' => (int) ($row?->active ?? 0),
            'overdue' => (int) ($row?->overdue ?? 0),
            'completed' => (int) ($row?->completed ?? 0),
        ];
    }

    public function repo_titleTypeCounts(AnalyticsFiltersDTO $filters): Collection
    {
        return $this->applyFormFilters(
            MissingFormModel::query()
                ->join(
                    'reg_title_type_models as analytics_title_types',
                    'analytics_title_types.title_type_code_unique',
                    '=',
                    'reg_missing_form_models.title_type_code_unique',
                ),
            $filters,
        )
            ->select([
                'analytics_title_types.title_type_code_unique',
                'analytics_title_types.title_type_code',
                'analytics_title_types.title_type_name',
            ])
            ->selectRaw('COUNT(*) as total')
            ->groupBy([
                'analytics_title_types.title_type_code_unique',
                'analytics_title_types.title_type_code',
                'analytics_title_types.title_type_name',
            ])
            ->orderByDesc('total')
            ->get();
    }

    public function repo_yearCounts(AnalyticsFiltersDTO $filters): Collection
    {
        return $this->applyFormFilters(MissingFormModel::query(), $filters)
            ->select('form_year')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('form_year')
            ->orderBy('form_year')
            ->get();
    }

    public function repo_locationPerformance(AnalyticsFiltersDTO $filters): Collection
    {
        return $this->historyQuery($filters)
            ->select([
                'mis_search_history_models.search_location_code_unique',
                'mis_search_history_models.search_location_name',
            ])
            ->selectRaw('COUNT(*) as searches')
            ->selectRaw(
                'SUM(CASE WHEN mis_search_history_models.mission_result = ? THEN 1 ELSE 0 END) as found',
                [MissionResult::Found->value],
            )
            ->groupBy([
                'mis_search_history_models.search_location_code_unique',
                'mis_search_history_models.search_location_name',
            ])
            ->orderByDesc('searches')
            ->get();
    }

    public function repo_responsiblePerformance(AnalyticsFiltersDTO $filters): Collection
    {
        return $this->historyQuery($filters)
            ->select([
                'mis_search_history_models.responsible_user_code_unique',
                'mis_search_history_models.responsible_name',
            ])
            ->selectRaw('COUNT(*) as searches')
            ->selectRaw(
                'SUM(CASE WHEN mis_search_history_models.mission_result = ? THEN 1 ELSE 0 END) as found',
                [MissionResult::Found->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN mis_search_history_models.mission_result = ? THEN 1 ELSE 0 END) as not_found',
                [MissionResult::NotFound->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN mis_search_history_models.mission_result = ? THEN 1 ELSE 0 END) as needs_verification',
                [MissionResult::NeedsVerification->value],
            )
            ->groupBy([
                'mis_search_history_models.responsible_user_code_unique',
                'mis_search_history_models.responsible_name',
            ])
            ->orderByDesc('searches')
            ->get();
    }

    public function repo_monthlySearchCounts(AnalyticsFiltersDTO $filters): Collection
    {
        $expression = $this->monthExpression('mis_search_history_models.searched_on');

        return $this->historyQuery($filters)
            ->whereDate(
                'mis_search_history_models.searched_on',
                '>=',
                now()->subMonths($filters->trendMonths - 1)->startOfMonth()->toDateString(),
            )
            ->selectRaw("{$expression} as month")
            ->selectRaw('COUNT(*) as searches')
            ->selectRaw(
                'SUM(CASE WHEN mis_search_history_models.mission_result = ? THEN 1 ELSE 0 END) as found',
                [MissionResult::Found->value],
            )
            ->groupBy(DB::raw($expression))
            ->orderBy('month')
            ->get();
    }

    public function repo_monthlyResolutionCounts(AnalyticsFiltersDTO $filters): Collection
    {
        $expression = $this->monthExpression('reg_missing_form_models.resolved_at');

        return $this->applyFormFilters(MissingFormModel::query(), $filters)
            ->whereNotNull('resolved_at')
            ->whereDate(
                'resolved_at',
                '>=',
                now()->subMonths($filters->trendMonths - 1)->startOfMonth()->toDateString(),
            )
            ->selectRaw("{$expression} as month")
            ->selectRaw('COUNT(*) as resolved')
            ->groupBy(DB::raw($expression))
            ->orderBy('month')
            ->get();
    }

    public function repo_monthlyRegistrationCounts(AnalyticsFiltersDTO $filters): Collection
    {
        $expression = $this->monthExpression('reg_missing_form_models.created_at');

        return $this->applyFormFilters(MissingFormModel::query(), $filters)
            ->whereDate(
                'created_at',
                '>=',
                now()->subMonths($filters->trendMonths - 1)->startOfMonth()->toDateString(),
            )
            ->selectRaw("{$expression} as month")
            ->selectRaw('COUNT(*) as registrations')
            ->groupBy(DB::raw($expression))
            ->orderBy('month')
            ->get();
    }

    public function repo_dashboardAttention(
        ?string $responsibleUserCodeUnique,
        bool $includeMissionQueue,
        bool $includeAssignmentQueue,
        bool $includeReviewQueue,
        bool $includeFinalizationQueues,
        int $limit = 5,
    ): array {
        $activeMissionStatuses = [
            MissionStatus::Assigned->value,
            MissionStatus::InProgress->value,
        ];
        $activeMissions = SearchMissionModel::query()
            ->with([
                'missingForm.titleType',
                'responsible',
                'searchLocation',
            ])
            ->whereIn('status', $activeMissionStatuses)
            ->whereHas('missingForm')
            ->when(
                $responsibleUserCodeUnique,
                fn (Builder $query, string $responsible): Builder => $query
                    ->where('responsible_user_code_unique', $responsible),
            );

        $overdueQuery = (clone $activeMissions)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today());
        $dueSoonQuery = (clone $activeMissions)
            ->whereBetween('due_date', [
                today()->toDateString(),
                today()->addDays(2)->toDateString(),
            ]);

        $assignmentQuery = MissingFormModel::query()
            ->with(['titleType', 'lastResponsible', 'lastSearchLocation'])
            ->where('status', FormStatus::Missing->value)
            ->whereDoesntHave('missions', function (Builder $query) use ($activeMissionStatuses): void {
                $query
                    ->whereIn('status', $activeMissionStatuses)
                    ->whereColumn(
                        'mis_search_mission_models.tracking_cycle',
                        'reg_missing_form_models.tracking_cycle',
                    );
            });
        $reviewQuery = MissingFormModel::query()
            ->with(['titleType', 'lastResponsible', 'lastSearchLocation'])
            ->where('status', FormStatus::NeedsVerification->value);
        $digitizationQuery = MissingFormModel::query()
            ->with(['titleType', 'lastResponsible', 'lastSearchLocation'])
            ->where('status', FormStatus::Found->value);
        $entryQuery = MissingFormModel::query()
            ->with(['titleType', 'lastResponsible', 'lastSearchLocation'])
            ->where('status', FormStatus::Digitized->value);

        return [
            'counts' => [
                'formsWithoutMission' => $includeAssignmentQueue
                    ? (clone $assignmentQuery)->count()
                    : 0,
                'overdueMissions' => $includeMissionQueue
                    ? (clone $overdueQuery)->count()
                    : 0,
                'dueSoonMissions' => $includeMissionQueue
                    ? (clone $dueSoonQuery)->count()
                    : 0,
                'pendingReviews' => $includeReviewQueue
                    ? (clone $reviewQuery)->count()
                    : 0,
                'awaitingDigitization' => $includeFinalizationQueues
                    ? (clone $digitizationQuery)->count()
                    : 0,
                'awaitingEntry' => $includeFinalizationQueues
                    ? (clone $entryQuery)->count()
                    : 0,
            ],
            'overdueMissions' => $includeMissionQueue
                ? $this->orderMissionAttention($overdueQuery)->limit($limit)->get()
                : collect(),
            'dueSoonMissions' => $includeMissionQueue
                ? $this->orderMissionAttention($dueSoonQuery)->limit($limit)->get()
                : collect(),
            'formsWithoutMission' => $includeAssignmentQueue
                ? $this->orderFormAttention($assignmentQuery)->limit($limit)->get()
                : collect(),
            'pendingReviews' => $includeReviewQueue
                ? $this->orderFormAttention($reviewQuery)->limit($limit)->get()
                : collect(),
            'awaitingDigitization' => $includeFinalizationQueues
                ? $this->orderFormAttention($digitizationQuery)->limit($limit)->get()
                : collect(),
            'awaitingEntry' => $includeFinalizationQueues
                ? $this->orderFormAttention($entryQuery)->limit($limit)->get()
                : collect(),
        ];
    }

    public function repo_priorityForms(
        ?string $responsibleUserCodeUnique,
        int $limit = 8,
    ): Collection {
        $query = MissingFormModel::query()
            ->with(['titleType', 'lastResponsible', 'lastSearchLocation'])
            ->whereIn('priority', [
                FormPriority::Urgent->value,
                FormPriority::High->value,
            ])
            ->whereNotIn('status', [
                FormStatus::Entered->value,
                FormStatus::Archived->value,
            ])
            ->when(
                $responsibleUserCodeUnique,
                fn (Builder $query, string $responsible): Builder => $query
                    ->where('last_responsible_user_code_unique', $responsible),
            );

        return $this->orderFormAttention($query)
            ->limit($limit)
            ->get();
    }

    private function historyQuery(AnalyticsFiltersDTO $filters): Builder
    {
        $query = SearchHistoryModel::query()
            ->join(
                'reg_missing_form_models as analytics_forms',
                'analytics_forms.missing_form_code_unique',
                '=',
                'mis_search_history_models.missing_form_code_unique',
            )
            ->whereNull('analytics_forms.deleted_at');

        $this->applyFormFilters($query, $filters, 'analytics_forms', false);

        return $query
            ->when(
                $filters->responsibleUserCodeUnique,
                fn (Builder $builder, string $responsible): Builder => $builder->where(
                    'mis_search_history_models.responsible_user_code_unique',
                    $responsible,
                ),
            )
            ->when(
                $filters->searchLocationCodeUnique,
                fn (Builder $builder, string $location): Builder => $builder->where(
                    'mis_search_history_models.search_location_code_unique',
                    $location,
                ),
            );
    }

    private function applyFormFilters(
        Builder $query,
        AnalyticsFiltersDTO $filters,
        string $table = 'reg_missing_form_models',
        bool $includeOperationalFilters = true,
    ): Builder {
        return $query
            ->when(
                $filters->titleTypeCodeUnique,
                fn (Builder $builder, string $titleType): Builder => $builder->where(
                    "{$table}.title_type_code_unique",
                    $titleType,
                ),
            )
            ->when(
                $filters->formYear,
                fn (Builder $builder, int $year): Builder => $builder->where("{$table}.form_year", $year),
            )
            ->when(
                $filters->status,
                fn (Builder $builder, $status): Builder => $builder->where("{$table}.status", $status->value),
            )
            ->when(
                $filters->priority,
                fn (Builder $builder, $priority): Builder => $builder->where("{$table}.priority", $priority->value),
            )
            ->when(
                $includeOperationalFilters && $filters->responsibleUserCodeUnique,
                fn (Builder $builder, string $responsible): Builder => $builder->where(
                    "{$table}.last_responsible_user_code_unique",
                    $responsible,
                ),
            )
            ->when(
                $includeOperationalFilters && $filters->searchLocationCodeUnique,
                fn (Builder $builder, string $location): Builder => $builder->where(
                    "{$table}.last_search_location_code_unique",
                    $location,
                ),
            )
            ->when(
                $filters->registeredFrom,
                fn (Builder $builder, $date): Builder => $builder->where("{$table}.created_at", '>=', $date),
            )
            ->when(
                $filters->registeredTo,
                fn (Builder $builder, $date): Builder => $builder->where("{$table}.created_at", '<=', $date),
            );
    }

    private function orderMissionAttention(Builder $query): Builder
    {
        return $query
            ->orderByRaw($this->priorityOrderExpression('mis_search_mission_models.priority'))
            ->orderBy('due_date')
            ->orderBy('id');
    }

    private function orderFormAttention(Builder $query): Builder
    {
        return $query
            ->orderByRaw($this->priorityOrderExpression('reg_missing_form_models.priority'))
            ->orderByRaw('CASE WHEN search_due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('search_due_date')
            ->orderBy('id');
    }

    private function priorityOrderExpression(string $column): string
    {
        return "CASE {$column} "
            ."WHEN '".FormPriority::Urgent->value."' THEN 1 "
            ."WHEN '".FormPriority::High->value."' THEN 2 "
            ."WHEN '".FormPriority::Normal->value."' THEN 3 "
            .'ELSE 4 END';
    }

    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE_FORMAT({$column}, '%Y-%m')",
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            default => "strftime('%Y-%m', {$column})",
        };
    }
}
