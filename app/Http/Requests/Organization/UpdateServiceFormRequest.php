<?php

namespace App\Http\Requests\Organization;

use App\Enums\Administration\PermissionCode;
use App\Models\Organization\ServiceModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateServiceFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::SettingsManage->value) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $service = $this->route('service');
        $serviceId = $service instanceof ServiceModel ? $service->getKey() : null;

        return [
            'directorate_code_unique' => ['required', 'string', 'exists:org_directorate_models,directorate_code_unique'],
            'service_code' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('org_service_models', 'service_code')->ignore($serviceId),
            ],
            'abbreviation' => ['nullable', 'string', 'max:30'],
            'service_name' => [
                'required',
                'string',
                'max:200',
                Rule::unique('org_service_models', 'service_name')
                    ->where('directorate_code_unique', $this->input('directorate_code_unique'))
                    ->ignore($serviceId),
            ],
            'display_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'directorate_code_unique.required' => 'Sélectionnez la direction de rattachement.',
            'service_code.required' => 'Saisissez le code du service.',
            'service_code.regex' => 'Le code accepte uniquement les lettres majuscules, chiffres et tirets bas.',
            'service_code.unique' => 'Ce code de service est déjà utilisé.',
            'service_name.required' => 'Saisissez le nom du service.',
            'service_name.unique' => 'Ce service existe déjà dans cette direction.',
            'display_order.required' => 'Saisissez l’ordre d’affichage.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'directorate_code_unique' => trim((string) $this->input('directorate_code_unique')),
            'service_code' => Str::upper(trim((string) $this->input('service_code'))),
            'abbreviation' => $this->nullableUppercase('abbreviation'),
            'service_name' => trim((string) $this->input('service_name')),
        ]);
    }

    private function nullableUppercase(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : Str::upper($value);
    }
}
