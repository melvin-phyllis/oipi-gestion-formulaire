<?php

namespace App\Http\Requests\Mission;

use App\Enums\Administration\PermissionCode;
use App\Enums\Registry\FormPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignSearchMissionFormRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'search_location_code_unique.required' => 'Sélectionnez une localisation de recherche.',
            'search_location_code_unique.exists' => 'La localisation sélectionnée est introuvable.',
            'responsible_user_code_unique.required' => 'Sélectionnez un responsable.',
            'responsible_user_code_unique.exists' => 'Le responsable sélectionné est introuvable.',
            'due_date.after_or_equal' => 'L’échéance ne peut pas être située dans le passé.',
            'priority.required' => 'Sélectionnez une priorité.',
        ];
    }
}
