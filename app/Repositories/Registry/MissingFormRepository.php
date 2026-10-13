<?php

namespace App\Repositories\Registry;

use App\Dto\Registry\CreateMissingFormDTO;
use App\Dto\Registry\MissingFormFiltersDTO;
use App\Dto\Registry\UpdateMissingFormDTO;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\TitleTypeModel;
use App\Repositories\Interfaces\Registry\MissingFormRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class MissingFormRepository implements MissingFormRepositoryInterface
{
    public function repo_paginate(MissingFormFiltersDTO $filters, int $perPage = 20): LengthAwarePaginator
    {
        return MissingFormModel::query()
            ->with(['titleType', 'lastSearchLocation', 'lastResponsible'])
            ->withExists([
                'missions as has_active_missions' => fn (Builder $query): Builder => $query
                    ->whereIn('status', [MissionStatus::Assigned->value, MissionStatus::InProgress->value]),
            ])
            ->when($filters->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('registry_number', 'like', "%{$search}%")
                        ->orWhere('form_number', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters->titleTypeCodeUnique,
                fn (Builder $query, string $code) => $query->where('title_type_code_unique', $code),
            )
            ->when(
                $filters->formYear,
                fn (Builder $query, int $year) => $query->where('form_year', $year),
            )
            ->when(
                $filters->status,
                fn (Builder $query, FormStatus $status) => $query->where('status', $status->value),
            )
            ->when(
                $filters->priority,
                fn (Builder $query, FormPriority $priority) => $query->where('priority', $priority->value),
            )
            ->latest('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function repo_findActiveTitleType(string $codeUnique): ?TitleTypeModel
    {
        return TitleTypeModel::query()
            ->where('title_type_code_unique', $codeUnique)
            ->where('is_active', true)
            ->first();
    }

    public function repo_loadForDisplay(MissingFormModel $form): MissingFormModel
    {
        return $form->load([
            'titleType',
            'lastSearchLocation',
            'lastResponsible.role',
            'lastResponsible.service.directorate',
            'missions' => fn ($query) => $query->latest('assigned_at'),
            'missions.parentMission',
            'missions.searchLocation',
            'missions.responsible.role',
            'missions.responsible.service.directorate',
            'missions.assignedBy',
            'missions.closedBy',
            'missions.foundReview.reviewedBy',
            'createdBy',
            'searchHistory' => fn ($query) => $query
                ->orderByDesc('searched_on')
                ->orderByDesc('id'),
            'searchHistory.searchLocation',
            'searchHistory.responsible',
            'searchHistory.recordedBy',
            'directFoundReport.finder',
            'directFoundReport.foundLocation',
            'directFoundReport.recordedBy',
            'foundReviews' => fn ($query) => $query->latest('reviewed_at'),
            'foundReviews.mission',
            'foundReviews.reviewedBy',
            'finalizations' => fn ($query) => $query->latest('performed_at'),
            'finalizations.performedBy',
            'reopenings' => fn ($query) => $query->latest('reopened_at'),
            'reopenings.reopenedBy',
            'attachments' => fn ($query) => $query->latest('created_at'),
            'attachments.uploadedBy',
        ]);
    }

    public function repo_hasActiveMissions(string $formCodeUnique): bool
    {
        return SearchMissionModel::query()
            ->where('missing_form_code_unique', $formCodeUnique)
            ->whereIn('status', [MissionStatus::Assigned->value, MissionStatus::InProgress->value])
            ->exists();
    }

    public function repo_businessIdentityExists(
        int $formYear,
        string $titleTypeCodeUnique,
        string $formNumber,
        ?string $exceptCodeUnique = null,
    ): bool {
        return MissingFormModel::withTrashed()
            ->where('form_year', $formYear)
            ->where('title_type_code_unique', $titleTypeCodeUnique)
            ->where('form_number', $formNumber)
            ->when(
                $exceptCodeUnique,
                fn ($query) => $query->where('missing_form_code_unique', '!=', $exceptCodeUnique),
            )
            ->exists();
    }

    public function repo_existingBusinessIdentityKeys(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $identities = [];

        foreach (array_chunk($rows, 250) as $chunk) {
            $matches = MissingFormModel::withTrashed()
                ->where(function (Builder $query) use ($chunk): void {
                    foreach ($chunk as $row) {
                        $query->orWhere(function (Builder $identityQuery) use ($row): void {
                            $identityQuery
                                ->where('form_year', $row['form_year'])
                                ->where('title_type_code_unique', $row['title_type_code_unique'])
                                ->where('form_number', $row['form_number']);
                        });
                    }
                })
                ->get(['form_year', 'title_type_code_unique', 'form_number']);

            foreach ($matches as $form) {
                $identities[] = implode('|', [
                    $form->form_year,
                    $form->title_type_code_unique,
                    $form->form_number,
                ]);
            }
        }

        return array_values(array_unique($identities));
    }

    public function repo_bulkCreateImported(
        array $rows,
        string $actorCodeUnique,
        string $importCodeUnique,
    ): void {
        if ($rows === []) {
            return;
        }

        $now = now();
        $payload = [];

        foreach ($rows as $row) {
            $uuid = (string) Str::uuid();
            $payload[] = [
                'uuid' => $uuid,
                'missing_form_code_unique' => 'FRM-'.Str::ulid(),
                'registry_number' => 'PENDING-'.$uuid,
                'title_type_code_unique' => $row['title_type_code_unique'],
                'form_year' => $row['form_year'],
                'form_number' => $row['form_number'],
                'status' => FormStatus::Missing->value,
                'priority' => $row['priority'],
                'tracking_cycle' => 1,
                'created_by_user_code_unique' => $actorCodeUnique,
                'updated_by_user_code_unique' => $actorCodeUnique,
                'source_import_code_unique' => $importCodeUnique,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('reg_missing_form_models')->insert($payload);
    }

    public function repo_finalizeImportedRegistryNumbers(string $importCodeUnique): void
    {
        $year = now()->year;
        $driver = DB::connection()->getDriverName();
        $expression = match ($driver) {
            'mysql', 'mariadb' => "CONCAT('FM-{$year}-', LPAD(id, 6, '0'))",
            'sqlite' => "printf('FM-{$year}-%06d', id)",
            default => null,
        };

        if ($expression !== null) {
            DB::table('reg_missing_form_models')
                ->where('source_import_code_unique', $importCodeUnique)
                ->update(['registry_number' => DB::raw($expression)]);

            return;
        }

        MissingFormModel::query()
            ->where('source_import_code_unique', $importCodeUnique)
            ->eachById(function (MissingFormModel $form) use ($year): void {
                $form->update([
                    'registry_number' => sprintf('FM-%d-%06d', $year, $form->getKey()),
                ]);
            });
    }

    public function repo_create(CreateMissingFormDTO $data, string $actorCodeUnique): MissingFormModel
    {
        $form = MissingFormModel::query()->create([
            'registry_number' => 'PENDING-'.Str::uuid(),
            'title_type_code_unique' => $data->titleTypeCodeUnique,
            'form_year' => $data->formYear,
            'form_number' => $data->formNumber,
            'status' => FormStatus::Missing,
            'priority' => $data->priority,
            'created_by_user_code_unique' => $actorCodeUnique,
            'updated_by_user_code_unique' => $actorCodeUnique,
        ]);

        $form->update([
            'registry_number' => sprintf('FM-%d-%06d', now()->year, $form->getKey()),
        ]);

        return $form->refresh();
    }

    public function repo_updateMetadata(
        MissingFormModel $form,
        UpdateMissingFormDTO $data,
        string $actorCodeUnique,
    ): MissingFormModel {
        $form->update([
            'title_type_code_unique' => $data->titleTypeCodeUnique,
            'form_year' => $data->formYear,
            'form_number' => $data->formNumber,
            'priority' => $data->priority,
            'updated_by_user_code_unique' => $actorCodeUnique,
        ]);

        return $form->refresh();
    }

    public function repo_archive(
        MissingFormModel $form,
        FormStatus $previousStatus,
        string $reason,
        string $actorCodeUnique,
    ): MissingFormModel {
        $form->update([
            'status_before_archive' => $previousStatus,
            'status' => FormStatus::Archived,
            'archive_reason' => $reason,
            'archived_by_user_code_unique' => $actorCodeUnique,
            'archived_at' => now(),
            'updated_by_user_code_unique' => $actorCodeUnique,
        ]);

        return $form->refresh();
    }

    public function repo_restore(
        MissingFormModel $form,
        FormStatus $restoredStatus,
        string $actorCodeUnique,
    ): MissingFormModel {
        $form->update([
            'status' => $restoredStatus,
            'status_before_archive' => null,
            'archive_reason' => null,
            'archived_by_user_code_unique' => null,
            'archived_at' => null,
            'updated_by_user_code_unique' => $actorCodeUnique,
        ]);

        return $form->refresh();
    }
}
