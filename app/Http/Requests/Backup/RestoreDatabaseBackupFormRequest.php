<?php

namespace App\Http\Requests\Backup;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RestoreDatabaseBackupFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::BackupsManage->value) === true;
    }

    public function rules(): array
    {
        return [
            'confirmation_phrase' => [
                'required',
                'string',
                Rule::in([(string) config('risfm.backups.confirmation_phrase', 'RESTAURER OIPI')]),
            ],
            'current_password' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        $phrase = (string) config('risfm.backups.confirmation_phrase', 'RESTAURER OIPI');

        return [
            'confirmation_phrase.required' => 'La phrase de confirmation est obligatoire.',
            'confirmation_phrase.in' => "Saisissez exactement « {$phrase} ».",
            'current_password.required' => 'Votre mot de passe actuel est obligatoire.',
        ];
    }
}
