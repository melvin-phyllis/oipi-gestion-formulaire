<?php

namespace App\Http\Requests\Mission;

use App\Enums\Administration\PermissionCode;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MissionFiltersFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->can(PermissionCode::MissionsAssign->value) === true
            || $user?->can(PermissionCode::MissionsRecordAnyResult->value) === true
            || $user?->can(PermissionCode::MissionsRecordOwnResult->value) === true
            || $user?->can(PermissionCode::MissionsReviewFound->value) === true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'responsible_user_code_unique' => ['nullable', 'string', 'max:40', 'exists:adm_user_models,user_code_unique'],
            'search_location_code_unique' => ['nullable', 'string', 'max:40', 'exists:reg_search_location_models,search_location_code_unique'],
            'status' => ['nullable', Rule::enum(MissionStatus::class)],
            'priority' => ['nullable', Rule::enum(FormPriority::class)],
            'due_from' => ['nullable', 'date_format:Y-m-d'],
            'due_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:due_from'],
            'overdue_only' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => filled($this->input('search'))
                ? trim((string) $this->input('search'))
                : null,
        ]);
    }
}
