<?php

namespace App\Http\Requests\Registry;

use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

final class ImportMissingFormsFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::RegistryImport->value) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'import_file' => [
                'required',
                File::types(['csv', 'txt', 'xlsx'])
                    ->max((int) config('risfm.registry_import.max_file_size_kb', 20480)),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Sélectionnez un fichier CSV ou Excel à importer.',
            'import_file.uploaded' => 'Le fichier n’a pas pu être téléversé. Sa taille dépasse probablement la limite PHP du serveur (upload_max_filesize).',
            'import_file.file' => 'Le fichier d’import est invalide.',
            'import_file.mimes' => 'Le fichier doit être au format CSV ou XLSX.',
            'import_file.max' => 'Le fichier d’import dépasse la taille maximale autorisée.',
        ];
    }
}
