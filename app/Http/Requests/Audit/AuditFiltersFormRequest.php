<?php

namespace App\Http\Requests\Audit;

use App\Enums\Administration\PermissionCode;
use App\Enums\Audit\AuditAction;
use App\Enums\Audit\AuditCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AuditFiltersFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::AuditView->value) === true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', Rule::enum(AuditCategory::class)],
            'action' => ['nullable', Rule::enum(AuditAction::class)],
            'actor_user_code_unique' => ['nullable', 'string', 'max:40', 'exists:adm_user_models,user_code_unique'],
            'occurred_from' => ['nullable', 'date_format:Y-m-d'],
            'occurred_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:occurred_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'occurred_to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}
