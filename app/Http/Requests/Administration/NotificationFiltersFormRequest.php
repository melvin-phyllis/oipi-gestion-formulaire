<?php

namespace App\Http\Requests\Administration;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NotificationFiltersFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::NotificationsView->value) === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['all', 'unread', 'read'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ];
    }
}
