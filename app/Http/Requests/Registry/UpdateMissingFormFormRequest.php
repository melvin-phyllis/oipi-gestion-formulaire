<?php

namespace App\Http\Requests\Registry;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateMissingFormFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::RegistryUpdateMetadata->value) === true;
    }

    public function rules(): array
    {
        return CreateMissingFormFormRequest::metadataRules();
    }

    public function messages(): array
    {
        return CreateMissingFormFormRequest::metadataMessages();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(CreateMissingFormFormRequest::normalizedMetadata($this->all()));
    }
}
