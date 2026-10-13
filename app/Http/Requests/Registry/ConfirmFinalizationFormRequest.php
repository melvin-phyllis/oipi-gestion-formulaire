<?php

namespace App\Http\Requests\Registry;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;

final class ConfirmFinalizationFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::RegistryFinalize->value) === true;
    }

    public function rules(): array
    {
        return [
            'performed_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'performed_on.date_format' => 'La date de réalisation doit respecter le format JJ/MM/AAAA.',
            'performed_on.before_or_equal' => 'La date de réalisation ne peut pas être située dans le futur.',
        ];
    }
}
