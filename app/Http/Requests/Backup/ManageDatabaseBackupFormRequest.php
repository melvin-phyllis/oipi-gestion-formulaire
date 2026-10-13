<?php

namespace App\Http\Requests\Backup;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class ManageDatabaseBackupFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::BackupsManage->value) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
