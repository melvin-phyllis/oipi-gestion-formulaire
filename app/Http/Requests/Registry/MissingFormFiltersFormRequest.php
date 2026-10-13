<?php

namespace App\Http\Requests\Registry;

use App\Enums\Administration\PermissionCode;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MissingFormFiltersFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::RegistryView->value) === true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'title_type_code_unique' => ['nullable', 'string', 'max:40', 'exists:reg_title_type_models,title_type_code_unique'],
            'form_year' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'status' => ['nullable', Rule::enum(FormStatus::class)],
            'priority' => ['nullable', Rule::enum(FormPriority::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => filled($this->input('search'))
                ? trim((string) $this->input('search'))
                : null,
        ]);
    }
}
