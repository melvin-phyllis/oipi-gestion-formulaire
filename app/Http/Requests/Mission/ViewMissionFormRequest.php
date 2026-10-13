<?php

namespace App\Http\Requests\Mission;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class ViewMissionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->can(PermissionCode::MissionsAssign->value) === true
            || $user?->can(PermissionCode::MissionsRecordAnyResult->value) === true
            || $user?->can(PermissionCode::MissionsRecordOwnResult->value) === true
            || $user?->can(PermissionCode::MissionsReviewFound->value) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
