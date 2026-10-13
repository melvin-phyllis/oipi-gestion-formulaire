<?php

namespace App\Http\Requests\Registry;

use App\Enums\Administration\PermissionCode;
use App\Enums\Registry\FinderType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class RegisterFoundMissingFormFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::RegistryRegisterFound->value) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return CreateMissingFormFormRequest::metadataRules() + [
            'finder_type' => ['required', Rule::enum(FinderType::class)],
            'finder_user_code_unique' => [
                Rule::requiredIf($this->input('finder_type') === FinderType::Internal->value),
                Rule::prohibitedIf($this->input('finder_type') === FinderType::External->value),
                'nullable',
                'string',
                'max:40',
                Rule::exists('adm_user_models', 'user_code_unique')
                    ->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
            ],
            'finder_name' => [
                Rule::requiredIf($this->input('finder_type') === FinderType::External->value),
                Rule::prohibitedIf($this->input('finder_type') === FinderType::Internal->value),
                'nullable',
                'string',
                'max:220',
            ],
            'finder_organization' => ['nullable', 'string', 'max:255'],
            'finder_contact' => ['nullable', 'string', 'max:150'],
            'found_location_code_unique' => [
                'required',
                'string',
                'max:40',
                Rule::exists('reg_search_location_models', 'search_location_code_unique')
                    ->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
            ],
            'found_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'result_summary' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return CreateMissingFormFormRequest::metadataMessages() + [
            'finder_type.required' => 'Précisez si la personne est interne ou externe à RISFM.',
            'finder_type.enum' => 'Le type de personne sélectionné est invalide.',
            'finder_user_code_unique.required' => 'Sélectionnez la personne ayant retrouvé le formulaire.',
            'finder_user_code_unique.prohibited' => 'Une personne externe ne doit pas être liée à un compte RISFM.',
            'finder_user_code_unique.exists' => 'Le compte sélectionné est introuvable ou inactif.',
            'finder_name.required' => 'Saisissez le nom de la personne externe.',
            'finder_name.prohibited' => 'Le nom d’un utilisateur RISFM est récupéré automatiquement.',
            'found_location_code_unique.required' => 'Sélectionnez la localisation où le formulaire a été retrouvé.',
            'found_location_code_unique.exists' => 'La localisation sélectionnée est introuvable ou inactive.',
            'found_on.required' => 'Saisissez la date de découverte.',
            'found_on.date_format' => 'La date de découverte doit respecter le format AAAA-MM-JJ.',
            'found_on.before_or_equal' => 'La date de découverte ne peut pas être située dans le futur.',
            'result_summary.required' => 'Saisissez un compte rendu de la découverte.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $metadata = CreateMissingFormFormRequest::normalizedMetadata($this->all());

        $this->merge($metadata + [
            'finder_type' => Str::lower(trim((string) $this->input('finder_type'))),
            'finder_user_code_unique' => $this->nullableTrimmed('finder_user_code_unique'),
            'finder_name' => $this->nullableTrimmed('finder_name'),
            'finder_organization' => $this->nullableTrimmed('finder_organization'),
            'finder_contact' => $this->nullableTrimmed('finder_contact'),
            'found_location_code_unique' => trim((string) $this->input('found_location_code_unique')),
            'result_summary' => trim((string) $this->input('result_summary')),
        ]);
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
