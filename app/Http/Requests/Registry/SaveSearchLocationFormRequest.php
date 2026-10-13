<?php

namespace App\Http\Requests\Registry;

use App\Models\Registry\SearchLocationModel;
use Illuminate\Validation\Rule;

final class SaveSearchLocationFormRequest extends RegistrySettingsFormRequest
{
    public function rules(): array
    {
        $location = $this->route('searchLocation');
        $id = $location instanceof SearchLocationModel ? $location->getKey() : null;

        return [
            'location_name' => ['required', 'string', 'max:150', Rule::unique('reg_search_location_models', 'location_name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
            'display_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_name.required' => 'Saisissez le nom de la localisation.',
            'location_name.unique' => 'Cette localisation existe déjà.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $description = trim((string) $this->input('description'));
        $this->merge([
            'location_name' => trim((string) $this->input('location_name')),
            'description' => $description === '' ? null : $description,
        ]);
    }
}
