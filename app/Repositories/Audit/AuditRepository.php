<?php

namespace App\Repositories\Audit;

use App\Dto\Audit\AuditFiltersDTO;
use App\Dto\Audit\RecordAuditDTO;
use App\Enums\Audit\AuditCategory;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Audit\AuditLogModel;
use App\Models\Backup\DatabaseBackupModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use App\Models\Registry\FormAttachmentModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Models\Registry\TitleTypeModel;
use App\Repositories\Interfaces\Audit\AuditRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class AuditRepository implements AuditRepositoryInterface
{
    public function repo_create(RecordAuditDTO $data): AuditLogModel
    {
        return AuditLogModel::query()->create([
            'category' => $data->action->category()->value,
            'action_code' => $data->action->value,
            'actor_user_code_unique' => $data->actorUserCodeUnique,
            'actor_name' => $data->actorName,
            'subject_type' => $data->subjectType,
            'subject_uuid' => $data->subjectUuid,
            'subject_code_unique' => $data->subjectCodeUnique,
            'subject_label' => $data->subjectLabel,
            'description' => $data->description,
            'route_name' => $data->routeName,
            'http_method' => $data->httpMethod,
            'request_path' => $data->requestPath,
            'ip_address' => $data->ipAddress,
            'user_agent' => $data->userAgent,
            'metadata' => $data->metadata ?: null,
            'occurred_at' => $data->occurredAt,
        ]);
    }

    public function repo_paginate(AuditFiltersDTO $filters, int $perPage = 25): LengthAwarePaginator
    {
        return AuditLogModel::query()
            ->when($filters->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('actor_name', 'like', "%{$search}%")
                        ->orWhere('subject_label', 'like', "%{$search}%")
                        ->orWhere('subject_code_unique', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters->category,
                fn (Builder $query, $category): Builder => $query->where('category', $category->value),
            )
            ->when(
                $filters->action,
                fn (Builder $query, $action): Builder => $query->where('action_code', $action->value),
            )
            ->when(
                $filters->actorUserCodeUnique,
                fn (Builder $query, string $actor): Builder => $query
                    ->where('actor_user_code_unique', $actor),
            )
            ->when(
                $filters->occurredFrom,
                fn (Builder $query, $date): Builder => $query->where('occurred_at', '>=', $date),
            )
            ->when(
                $filters->occurredTo,
                fn (Builder $query, $date): Builder => $query->where('occurred_at', '<=', $date),
            )
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function repo_actorOptions(): Collection
    {
        return AuditLogModel::query()
            ->whereNotNull('actor_user_code_unique')
            ->select(['actor_user_code_unique', 'actor_name'])
            ->distinct()
            ->orderBy('actor_name')
            ->get();
    }

    public function repo_recentOperationalActivities(
        ?string $actorUserCodeUnique = null,
        int $limit = 8,
    ): Collection {
        return AuditLogModel::query()
            ->whereIn('category', [
                AuditCategory::Registry->value,
                AuditCategory::Mission->value,
            ])
            ->when(
                $actorUserCodeUnique,
                fn (Builder $query, string $actor): Builder => $query
                    ->where('actor_user_code_unique', $actor),
            )
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function repo_findUserByUuid(string $uuid): ?UserModel
    {
        return UserModel::withTrashed()->where('uuid', $uuid)->first();
    }

    public function repo_resolveSubject(string $type, string $uuid): array
    {
        $model = match ($type) {
            'user' => UserModel::withTrashed()->where('uuid', $uuid)->first(),
            'role' => RoleModel::withTrashed()->where('uuid', $uuid)->first(),
            'missing_form' => MissingFormModel::withTrashed()->where('uuid', $uuid)->first(),
            'mission' => SearchMissionModel::withTrashed()->where('uuid', $uuid)->first(),
            'directorate' => DirectorateModel::withTrashed()->where('uuid', $uuid)->first(),
            'service' => ServiceModel::withTrashed()->where('uuid', $uuid)->first(),
            'title_type' => TitleTypeModel::withTrashed()->where('uuid', $uuid)->first(),
            'search_location' => SearchLocationModel::withTrashed()->where('uuid', $uuid)->first(),
            'database_backup' => DatabaseBackupModel::withTrashed()->where('uuid', $uuid)->first(),
            'form_attachment' => FormAttachmentModel::withTrashed()->where('uuid', $uuid)->first(),
            default => null,
        };

        if (! $model instanceof Model) {
            return ['codeUnique' => null, 'label' => null];
        }

        return match ($type) {
            'user' => [
                'codeUnique' => $model->user_code_unique,
                'label' => trim("{$model->last_name} {$model->first_names}"),
            ],
            'role' => [
                'codeUnique' => $model->role_code_unique,
                'label' => $model->role_name,
            ],
            'missing_form' => [
                'codeUnique' => $model->missing_form_code_unique,
                'label' => $model->form_number,
            ],
            'mission' => [
                'codeUnique' => $model->search_mission_code_unique,
                'label' => $model->search_mission_code_unique,
            ],
            'directorate' => [
                'codeUnique' => $model->directorate_code_unique,
                'label' => $model->directorate_name,
            ],
            'service' => [
                'codeUnique' => $model->service_code_unique,
                'label' => $model->service_name,
            ],
            'title_type' => [
                'codeUnique' => $model->title_type_code_unique,
                'label' => $model->title_type_name,
            ],
            'search_location' => [
                'codeUnique' => $model->search_location_code_unique,
                'label' => $model->location_name,
            ],
            'database_backup' => [
                'codeUnique' => $model->database_backup_code_unique,
                'label' => $model->file_name,
            ],
            'form_attachment' => [
                'codeUnique' => $model->form_attachment_code_unique,
                'label' => $model->original_name,
            ],
            default => ['codeUnique' => null, 'label' => null],
        };
    }
}
