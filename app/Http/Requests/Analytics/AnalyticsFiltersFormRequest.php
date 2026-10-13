<?php

namespace App\Http\Requests\Analytics;

use App\Enums\Administration\PermissionCode;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AnalyticsFiltersFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::AnalyticsView->value) === true;
    }

    public function rules(): array
    {
        return [
            'title_type_code_unique' => ['nullable', 'string', 'max:40', 'exists:reg_title_type_models,title_type_code_unique'],
            'form_year' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'status' => ['nullable', Rule::enum(FormStatus::class)],
            'priority' => ['nullable', Rule::enum(FormPriority::class)],
            'responsible_user_code_unique' => ['nullable', 'string', 'max:40', 'exists:adm_user_models,user_code_unique'],
            'search_location_code_unique' => ['nullable', 'string', 'max:40', 'exists:reg_search_location_models,search_location_code_unique'],
            'registered_from' => ['nullable', 'date_format:Y-m-d'],
            'registered_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:registered_from'],
            'trend_months' => ['nullable', 'integer', Rule::in([6, 12, 24])],
        ];
    }

    public function messages(): array
    {
        return [
            'registered_to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'trend_months.in' => 'La période d’évolution doit être de 6, 12 ou 24 mois.',
        ];
    }
}
