<?php

namespace App\Http\Requests\Registry;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class ReopenMissingFormFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::RegistryReopen->value) === true;
    }

    public function rules(): array
    {
        return [
            'reopening_reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reopening_reason' => trim((string) $this->input('reopening_reason')),
        ]);
    }
}
