<?php

namespace App\Http\Requests\Mission;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class ConfirmFoundReportFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::MissionsReviewFound->value) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
