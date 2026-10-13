<?php

namespace App\Http\Requests\Mission;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class CancelSearchMissionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::MissionsAssign->value) === true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cancellation_reason' => trim((string) $this->input('cancellation_reason')),
        ]);
    }
}
