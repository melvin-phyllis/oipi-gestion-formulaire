<?php

namespace App\Http\Requests\Registry;

final class UpdateReferenceStatusFormRequest extends RegistrySettingsFormRequest
{
    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }
}
