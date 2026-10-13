<?php

namespace App\Http\Requests\Registry;

use App\Enums\Administration\PermissionCode;
use App\Models\Registry\MissingFormImportModel;
use Illuminate\Foundation\Http\FormRequest;

final class ViewMissingFormImportFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $import = $this->route('missingFormImport');

        return $user?->can(PermissionCode::RegistryImport->value) === true
            && $import instanceof MissingFormImportModel
            && $import->created_by_user_code_unique !== null
            && hash_equals(
                $import->created_by_user_code_unique,
                $user->user_code_unique,
            );
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
