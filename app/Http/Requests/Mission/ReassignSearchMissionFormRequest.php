<?php

namespace App\Http\Requests\Mission;

use App\Enums\Administration\PermissionCode;
use App\Enums\Registry\FormPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReassignSearchMissionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::MissionsAssign->value) === true;
    }

    public function rules(): array
    {
        return [
            'search_location_code_unique' => ['required', 'string', 'max:40', 'exists:reg_search_location_models,search_location_code_unique'],
            'responsible_user_code_unique' => ['required', 'string', 'max:40', 'exists:adm_user_models,user_code_unique'],
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'priority' => ['required', Rule::enum(FormPriority::class)],
            'reassignment_reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reassignment_reason' => trim((string) $this->input('reassignment_reason')),
        ]);
    }
}
