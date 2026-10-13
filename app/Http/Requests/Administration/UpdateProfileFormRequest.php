<?php

namespace App\Http\Requests\Administration;

use App\Enums\Administration\PermissionCode;
use App\Models\Administration\UserModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProfileFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::ProfileUpdate->value) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();
        $userId = $user instanceof UserModel ? $user->getKey() : null;
        $emailChanges = $user instanceof UserModel
            && mb_strtolower((string) $this->input('email')) !== mb_strtolower($user->email);

        return [
            'last_name' => ['required', 'string', 'max:100'],
            'first_names' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email:rfc',
                'max:150',
                Rule::unique('adm_user_models', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'current_password' => [
                Rule::requiredIf($emailChanges),
                'nullable',
                'string',
                'max:255',
            ],
            'username' => ['prohibited'],
            'role_code_unique' => ['prohibited'],
            'service_code_unique' => ['prohibited'],
            'is_active' => ['prohibited'],
            'must_change_password' => ['prohibited'],
            'session_version' => ['prohibited'],
            'password' => ['prohibited'],
            'profile_photo_path' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'last_name.required' => 'Saisissez votre nom.',
            'first_names.required' => 'Saisissez vos prénoms.',
            'email.required' => 'Saisissez votre adresse e-mail.',
            'email.email' => 'Saisissez une adresse e-mail valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'current_password.required' => 'Saisissez votre mot de passe actuel pour modifier l’adresse e-mail.',
            'prohibited' => 'Le champ :attribute ne peut pas être modifié depuis votre profil.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
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
