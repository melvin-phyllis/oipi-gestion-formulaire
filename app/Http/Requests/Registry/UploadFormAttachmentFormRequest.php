<?php

namespace App\Http\Requests\Registry;

use App\Dto\Registry\UploadFormAttachmentDTO;
use App\Enums\Administration\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use RuntimeException;

final class UploadFormAttachmentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::AttachmentsAddAny->value) === true
            || $this->user()?->can(PermissionCode::AttachmentsAddOwn->value) === true;
    }

    public function rules(): array
    {
        return [
            'attachment' => [
                'required',
                File::types(config('risfm.attachments.allowed_extensions', []))
                    ->max((int) config('risfm.attachments.max_size_kb', 10240)),
                'extensions:pdf,jpg,jpeg,png',
            ],
        ];
    }

    public function messages(): array
    {
        $maxMegabytes = (int) ceil((int) config('risfm.attachments.max_size_kb', 10240) / 1024);

        return [
            'attachment.required' => 'Sélectionnez une pièce jointe.',
            'attachment.file' => 'La pièce jointe transmise est invalide.',
            'attachment.mimes' => 'La pièce jointe doit être un fichier PDF, JPG, JPEG ou PNG valide.',
            'attachment.extensions' => 'L’extension du fichier doit être PDF, JPG, JPEG ou PNG.',
            'attachment.max' => "La pièce jointe ne doit pas dépasser {$maxMegabytes} Mo.",
        ];
    }

    public function toDto(): UploadFormAttachmentDTO
    {
        $file = $this->file('attachment');
        if (! $file || ! $file->isValid()) {
            throw new RuntimeException('La pièce jointe temporaire est indisponible.');
        }

        $path = $file->getRealPath();
        if ($path === false) {
            throw new RuntimeException('La pièce jointe temporaire est illisible.');
        }

        return new UploadFormAttachmentDTO(
            temporaryPath: $path,
            originalName: $file->getClientOriginalName(),
            clientExtension: strtolower($file->getClientOriginalExtension()),
            clientMimeType: $file->getClientMimeType(),
            sizeBytes: (int) $file->getSize(),
        );
    }
}
