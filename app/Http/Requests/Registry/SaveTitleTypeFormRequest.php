<?php

namespace App\Http\Requests\Registry;

use App\Models\Registry\TitleTypeModel;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class SaveTitleTypeFormRequest extends RegistrySettingsFormRequest
{
    public function rules(): array
    {
        $titleType = $this->route('titleType');
        $id = $titleType instanceof TitleTypeModel ? $titleType->getKey() : null;

        return [
            'title_type_code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_]+$/', Rule::unique('reg_title_type_models', 'title_type_code')->ignore($id)],
            'title_type_name' => ['required', 'string', 'max:100', Rule::unique('reg_title_type_models', 'title_type_name')->ignore($id)],
            'display_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'title_type_code.required' => 'Saisissez l’abréviation du type de titre.',
            'title_type_code.regex' => 'L’abréviation accepte uniquement les lettres majuscules, chiffres et tirets bas.',
            'title_type_code.unique' => 'Cette abréviation est déjà utilisée.',
            'title_type_name.required' => 'Saisissez le nom du type de titre.',
            'title_type_name.unique' => 'Ce type de titre existe déjà.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title_type_code' => Str::upper(trim((string) $this->input('title_type_code'))),
            'title_type_name' => trim((string) $this->input('title_type_name')),
        ]);
    }
}
