<?php

namespace App\Http\Requests\Administration;

use App\Enums\Administration\PermissionCode;
use App\Models\Administration\UserModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::UsersManage->value) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof UserModel ? $user->getKey() : null;

        return [
            'role_code_unique' => ['required', 'string', 'exists:adm_role_models,role_code_unique'],
            'service_code_unique' => ['required', 'string', 'exists:org_service_models,service_code_unique'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('adm_user_models', 'username')->ignore($userId),
            ],
            'last_name' => ['required', 'string', 'max:100'],
            'first_names' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email:rfc',
                'max:150',
                Rule::unique('adm_user_models', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'role_code_unique.required' => 'Sélectionnez un rôle.',
            'service_code_unique.required' => 'Sélectionnez un service.',
            'username.required' => 'Saisissez un identifiant.',
            'username.regex' => 'L’identifiant accepte uniquement les lettres minuscules, chiffres, points, tirets et tirets bas.',
            'username.unique' => 'Cet identifiant est déjà utilisé.',
            'last_name.required' => 'Saisissez le nom.',
            'first_names.required' => 'Saisissez les prénoms.',
            'email.required' => 'Saisissez l’adresse e-mail.',
            'email.email' => 'Saisissez une adresse e-mail valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role_code_unique' => trim((string) $this->input('role_code_unique')),
            'service_code_unique' => trim((string) $this->input('service_code_unique')),
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'last_name' => trim((string) $this->input('last_name')),
            'first_names' => trim((string) $this->input('first_names')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => $this->nullableTrimmed('phone'),
        ]);
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
