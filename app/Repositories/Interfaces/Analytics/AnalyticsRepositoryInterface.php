<?php

namespace App\Repositories\Interfaces\Analytics;

use App\Dto\Analytics\AnalyticsFiltersDTO;
use App\Models\Registry\MissingFormModel;
use Illuminate\Support\Collection;

interface AnalyticsRepositoryInterface
{
    /** @return Collection<int, object> */
    public function repo_statusCounts(AnalyticsFiltersDTO $filters): Collection;

    /** @return array{total: int, active: int, overdue: int, completed: int} */
    public function repo_missionCounts(AnalyticsFiltersDTO $filters): array;

    /** @return Collection<int, object> */
    public function repo_titleTypeCounts(AnalyticsFiltersDTO $filters): Collection;

    /** @return Collection<int, object> */
    public function repo_yearCounts(AnalyticsFiltersDTO $filters): Collection;

    /** @return Collection<int, object> */
    public function repo_locationPerformance(AnalyticsFiltersDTO $filters): Collection;

    /** @return Collection<int, object> */
    public function repo_responsiblePerformance(AnalyticsFiltersDTO $filters): Collection;

    /** @return Collection<int, object> */
    public function repo_monthlySearchCounts(AnalyticsFiltersDTO $filters): Collection;

    /** @return Collection<int, object> */
    public function repo_monthlyResolutionCounts(AnalyticsFiltersDTO $filters): Collection;

    /** @return Collection<int, object> */
    public function repo_monthlyRegistrationCounts(AnalyticsFiltersDTO $filters): Collection;

    /**
     * @return array{
     *     counts: array{
     *         formsWithoutMission: int,
     *         overdueMissions: int,
     *         dueSoonMissions: int,
     *         pendingReviews: int,
     *         awaitingDigitization: int,
     *         awaitingEntry: int
     *     },
     *     overdueMissions: Collection<int, object>,
     *     dueSoonMissions: Collection<int, object>,
     *     formsWithoutMission: Collection<int, MissingFormModel>,
     *     pendingReviews: Collection<int, MissingFormModel>,
     *     awaitingDigitization: Collection<int, MissingFormModel>,
     *     awaitingEntry: Collection<int, MissingFormModel>
     * }
     */
    public function repo_dashboardAttention(
        ?string $responsibleUserCodeUnique,
        bool $includeMissionQueue,
        bool $includeAssignmentQueue,
        bool $includeReviewQueue,
        bool $includeFinalizationQueues,
        int $limit = 5,
    ): array;

    /** @return Collection<int, MissingFormModel> */
    public function repo_priorityForms(
        ?string $responsibleUserCodeUnique,
        int $limit = 8,
    ): Collection;
}
