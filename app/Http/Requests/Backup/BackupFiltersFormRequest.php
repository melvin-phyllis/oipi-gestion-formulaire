<?php

namespace App\Http\Requests\Backup;

use App\Enums\Administration\PermissionCode;
use App\Enums\Backup\BackupType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BackupFiltersFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::BackupsManage->value) === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(BackupType::class)],
            'created_from' => ['nullable', 'date_format:Y-m-d'],
            'created_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:created_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'created_to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}
