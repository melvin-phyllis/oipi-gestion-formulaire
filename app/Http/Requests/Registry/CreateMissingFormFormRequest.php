<?php

namespace App\Http\Requests\Registry;

use App\Enums\Administration\PermissionCode;
use App\Enums\Registry\FormPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class CreateMissingFormFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::RegistryCreate->value) === true;
    }

    public function rules(): array
    {
        return self::metadataRules();
    }

    public function messages(): array
    {
        return self::metadataMessages();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(self::normalizedMetadata($this->all()));
    }

    /** @return array<string, mixed> */
    public static function metadataRules(): array
    {
        return [
            'title_type_code_unique' => ['required', 'string', 'max:40', 'exists:reg_title_type_models,title_type_code_unique'],
            'form_year' => ['required', 'integer', 'min:1900', 'max:'.now()->year],
            'form_number' => ['required', 'string', 'max:60'],
            'priority' => ['required', Rule::enum(FormPriority::class)],
        ];
    }

    /** @return array<string, string> */
    public static function metadataMessages(): array
    {
        return [
            'title_type_code_unique.required' => 'Sélectionnez un type de titre.',
            'title_type_code_unique.exists' => 'Le type de titre sélectionné est introuvable.',
            'form_year.required' => 'Saisissez l’année du titre.',
            'form_year.max' => 'L’année du titre ne peut pas être située dans le futur.',
            'form_number.required' => 'Saisissez le numéro du formulaire.',
            'priority.required' => 'Sélectionnez une priorité.',
            'priority.enum' => 'La priorité sélectionnée est invalide.',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizedMetadata(array $input): array
    {
        return [
            'form_number' => Str::upper(trim((string) ($input['form_number'] ?? ''))),
            'priority' => $input['priority'] ?? FormPriority::Normal->value,
        ];
    }
}
