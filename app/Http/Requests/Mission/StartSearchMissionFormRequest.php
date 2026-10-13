<?php

namespace App\Http\Requests\Mission;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class StartSearchMissionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->can(PermissionCode::MissionsRecordAnyResult->value) === true
            || $user?->can(PermissionCode::MissionsRecordOwnResult->value) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
