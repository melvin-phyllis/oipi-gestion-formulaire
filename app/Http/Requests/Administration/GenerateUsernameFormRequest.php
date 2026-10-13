<?php

namespace App\Http\Requests\Administration;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class GenerateUsernameFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::UsersManage->value) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:100'],
            'first_names' => ['required', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'last_name.required' => 'Saisissez le nom avant de générer l’identifiant.',
            'first_names.required' => 'Saisissez au moins un prénom avant de générer l’identifiant.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'last_name' => trim((string) $this->input('last_name')),
            'first_names' => trim((string) $this->input('first_names')),
        ]);
    }
}
