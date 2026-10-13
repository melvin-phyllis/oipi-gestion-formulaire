<?php

namespace App\Http\Requests\Administration;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class ListUsersFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::UsersManage->value) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'role_code_unique' => ['nullable', 'string', 'exists:adm_role_models,role_code_unique'],
            'service_code_unique' => ['nullable', 'string', 'exists:org_service_models,service_code_unique'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [
            'search' => $this->normalizedNullable('search'),
            'role_code_unique' => $this->normalizedNullable('role_code_unique'),
            'service_code_unique' => $this->normalizedNullable('service_code_unique'),
        ];

        if ($this->filled('is_active')) {
            $data['is_active'] = $this->boolean('is_active');
        }

        $this->merge($data);
    }

    private function normalizedNullable(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
