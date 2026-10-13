<?php

namespace App\Http\Requests\Administration;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class ManageNotificationFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::NotificationsView->value) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
