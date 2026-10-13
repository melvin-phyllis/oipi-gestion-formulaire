<?php

namespace App\Http\Requests\Administration;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserStatusFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::UsersManage->value) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }
}
