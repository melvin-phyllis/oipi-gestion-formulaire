<?php

namespace App\Services\Mission;

use App\Contracts\Administration\UserDirectoryServiceInterface;
use App\Contracts\Registry\RegistryReferenceReadServiceInterface;
use App\Dto\Mission\MissionFiltersDTO;
use App\Enums\Administration\PermissionCode;
use App\Enums\Mission\MissionResult;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchMissionModel;
use App\Repositories\Interfaces\Mission\SearchMissionRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;

final class SearchMissionReadService
{
    public function __construct(
        private readonly SearchMissionRepositoryInterface $missions,
        private readonly UserDirectoryServiceInterface $users,
        private readonly RegistryReferenceReadServiceInterface $references,
    ) {}

    /** @return array<string, mixed> */
    public function srv_list(MissionFiltersDTO $filters, UserModel $viewer): array
    {
        $canViewAll = $this->canViewAll($viewer);

        return [
            'missions' => $this->missions->repo_paginate($filters, $viewer, $canViewAll),
            'responsibleUsers' => $canViewAll ? $this->users->srv_activeUsers() : collect([$viewer]),
            'searchLocations' => $this->references->srv_activeSearchLocations(),
            'statuses' => MissionStatus::cases(),
            'priorities' => FormPriority::cases(),
            'results' => MissionResult::cases(),
            'canViewAllMissions' => $canViewAll,
        ];
    }

    /** @return array<string, mixed> */
    public function srv_showData(SearchMissionModel $mission, UserModel $viewer): array
    {
        if (! $this->canViewAll($viewer)
            && $mission->responsible_user_code_unique !== $viewer->user_code_unique) {
            throw new AuthorizationException('Vous ne pouvez pas consulter cette mission.');
        }

        $mission = $this->missions->repo_loadForDisplay($mission);

        return [
            'mission' => $mission,
            'canAssign' => $viewer->can(PermissionCode::MissionsAssign->value),
            'responsibleUsers' => $this->users->srv_activeUsers(),
            'searchLocations' => $this->references->srv_activeSearchLocations(),
            'priorities' => FormPriority::cases(),
            'canRecordResult' => $mission->status->isActive() && (
                $viewer->can(PermissionCode::MissionsRecordAnyResult->value)
                || (
                    $mission->responsible_user_code_unique === $viewer->user_code_unique
                    && $viewer->can(PermissionCode::MissionsRecordOwnResult->value)
                )
            ),
            'canReviewFound' => $viewer->can(PermissionCode::MissionsReviewFound->value)
                && $mission->status === MissionStatus::Completed
                && in_array($mission->result, [MissionResult::Found, MissionResult::NeedsVerification], true)
                && $mission->missingForm?->status === FormStatus::NeedsVerification
                && $mission->tracking_cycle === $mission->missingForm?->tracking_cycle
                && $mission->foundReview === null,
        ];
    }

    private function canViewAll(UserModel $viewer): bool
    {
        return $viewer->can(PermissionCode::MissionsAssign->value)
            || $viewer->can(PermissionCode::MissionsRecordAnyResult->value)
            || $viewer->can(PermissionCode::MissionsReviewFound->value);
    }
}
