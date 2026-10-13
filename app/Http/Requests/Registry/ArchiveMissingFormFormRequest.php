<?php

namespace App\Http\Requests\Registry;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class ArchiveMissingFormFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::RegistryArchive->value) === true;
    }

    public function rules(): array
    {
        return [
            'archive_reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'archive_reason.required' => 'Le motif de l’archivage est obligatoire.',
            'archive_reason.min' => 'Le motif de l’archivage doit contenir au moins 5 caractères.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'archive_reason' => trim((string) $this->input('archive_reason')),
        ]);
    }
}
