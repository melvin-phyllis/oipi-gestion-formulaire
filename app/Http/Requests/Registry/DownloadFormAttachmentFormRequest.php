<?php

namespace App\Http\Requests\Registry;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class DownloadFormAttachmentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::RegistryView->value) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
