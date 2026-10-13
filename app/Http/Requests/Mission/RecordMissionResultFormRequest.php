<?php

namespace App\Http\Requests\Mission;

use App\Enums\Administration\PermissionCode;
use App\Enums\Mission\MissionResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordMissionResultFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->can(PermissionCode::MissionsRecordAnyResult->value) === true
            || $user?->can(PermissionCode::MissionsRecordOwnResult->value) === true;
    }

    public function rules(): array
    {
        return [
            'result' => ['required', Rule::enum(MissionResult::class)],
            'result_summary' => ['required', 'string', 'min:3', 'max:255'],
            'searched_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'result.required' => 'Sélectionnez le résultat de la recherche.',
            'result.enum' => 'Le résultat sélectionné est invalide.',
            'result_summary.required' => 'Saisissez un compte rendu de la recherche.',
            'searched_on.required' => 'Saisissez la date de recherche.',
            'searched_on.before_or_equal' => 'La date de recherche ne peut pas être située dans le futur.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'result_summary' => trim((string) $this->input('result_summary')),
        ]);
    }
}
