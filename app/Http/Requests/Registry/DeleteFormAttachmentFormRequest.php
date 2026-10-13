<?php

namespace App\Http\Requests\Registry;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteFormAttachmentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::AttachmentsDeleteAny->value) === true
            || $this->user()?->can(PermissionCode::AttachmentsDeleteOwn->value) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
