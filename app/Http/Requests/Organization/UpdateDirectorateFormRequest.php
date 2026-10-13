<?php

namespace App\Http\Requests\Organization;

use App\Enums\Administration\PermissionCode;
use App\Models\Organization\DirectorateModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateDirectorateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::SettingsManage->value) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $directorate = $this->route('directorate');
        $directorateId = $directorate instanceof DirectorateModel
            ? $directorate->getKey()
            : null;

        return [
            'directorate_code' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('org_directorate_models', 'directorate_code')->ignore($directorateId),
            ],
            'directorate_name' => [
                'required',
                'string',
                'max:200',
                Rule::unique('org_directorate_models', 'directorate_name')->ignore($directorateId),
            ],
            'display_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'directorate_code.required' => 'Saisissez le code de la direction.',
            'directorate_code.regex' => 'Le code accepte uniquement les lettres majuscules, chiffres et tirets bas.',
            'directorate_code.unique' => 'Ce code de direction est déjà utilisé.',
            'directorate_name.required' => 'Saisissez le nom de la direction.',
            'directorate_name.unique' => 'Cette direction existe déjà.',
            'display_order.required' => 'Saisissez l’ordre d’affichage.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'directorate_code' => Str::upper(trim((string) $this->input('directorate_code'))),
            'directorate_name' => trim((string) $this->input('directorate_name')),
        ]);
    }
}
