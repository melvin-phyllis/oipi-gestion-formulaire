<?php

namespace App\Services\Registry;

use App\Dto\Registry\AttachmentDownloadDTO;
use App\Dto\Registry\CreateFormAttachmentDTO;
use App\Dto\Registry\UploadFormAttachmentDTO;
use App\Enums\Administration\PermissionCode;
use App\Enums\Registry\FormStatus;
use App\Events\Registry\FormAttachmentAdded;
use App\Events\Registry\FormAttachmentDeleted;
use App\Events\Registry\FormAttachmentDownloaded;
use App\Models\Administration\UserModel;
use App\Models\Registry\FormAttachmentModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Registry\FormAttachmentRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class FormAttachmentService
{
    public function __construct(
        private readonly FormAttachmentRepositoryInterface $attachments,
    ) {}

    public function srv_add(
        MissingFormModel $form,
        UploadFormAttachmentDTO $data,
        UserModel $actor,
    ): FormAttachmentModel {
        $this->assertFormIsWritable($form);
        $this->assertCanAdd($form, $actor);

        $extension = strtolower($data->clientExtension);
        $mimeType = $this->detectedMimeType($data->temporaryPath);
        $sizeBytes = $this->verifiedSize($data->temporaryPath);
        if ($data->sizeBytes !== $sizeBytes) {
            throw ValidationException::withMessages([
                'attachment' => 'La taille de la pièce jointe a changé pendant son traitement.',
            ]);
        }
        $this->assertFileIsAllowed($extension, $mimeType, $sizeBytes);

        $uuid = (string) Str::uuid();
        $storedName = "{$uuid}.{$extension}";
        $disk = (string) config('risfm.attachments.disk', 'local');
        $this->assertPrivateDisk($disk);
        $directory = trim((string) config('risfm.attachments.directory', 'attachments/forms'), '/');
        $filePath = "{$directory}/{$form->uuid}/{$storedName}";
        $originalName = $this->safeOriginalName($data->originalName, $extension);
        $downloadName = $this->safeDownloadName($originalName, $extension);
        $sha256 = hash_file('sha256', $data->temporaryPath);
        if (! is_string($sha256)) {
            throw new RuntimeException('Impossible de calculer l’empreinte de la pièce jointe.');
        }

        $stream = fopen($data->temporaryPath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Impossible de lire la pièce jointe temporaire.');
        }

        try {
            $stored = Storage::disk($disk)->put($filePath, $stream, ['visibility' => 'private']);
        } finally {
            fclose($stream);
        }

        if (! $stored) {
            throw new RuntimeException('Impossible d’enregistrer la pièce jointe dans le stockage privé.');
        }

        try {
            $attachment = DB::transaction(fn (): FormAttachmentModel => $this->attachments->repo_create(
                new CreateFormAttachmentDTO(
                    uuid: $uuid,
                    missingFormCodeUnique: $form->missing_form_code_unique,
                    disk: $disk,
                    filePath: $filePath,
                    storedName: $storedName,
                    originalName: $originalName,
                    downloadName: $downloadName,
                    mimeType: $mimeType,
                    extension: $extension,
                    sizeBytes: $sizeBytes,
                    sha256: $sha256,
                    uploadedByUserCodeUnique: $actor->user_code_unique,
                    uploaderName: trim("{$actor->last_name} {$actor->first_names}"),
                ),
            ));
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($filePath);

            throw $exception;
        }

        FormAttachmentAdded::dispatch($attachment->uuid, $form->uuid, $actor->uuid);

        return $attachment;
    }

    public function srv_download(
        MissingFormModel $form,
        FormAttachmentModel $attachment,
        UserModel $actor,
    ): AttachmentDownloadDTO {
        $this->assertBelongsToForm($form, $attachment);
        $this->assertPrivateDisk($attachment->disk);
        if ($form->status === FormStatus::Archived
            && ! $actor->can(PermissionCode::RegistryArchive->value)) {
            throw new AuthorizationException('Vous ne pouvez pas télécharger une pièce jointe archivée.');
        }

        $disk = Storage::disk($attachment->disk);
        if (! $disk->exists($attachment->file_path)) {
            throw new RuntimeException('Le fichier physique de cette pièce jointe est introuvable.');
        }
        if ($disk->size($attachment->file_path) !== $attachment->size_bytes) {
            throw new RuntimeException('L’intégrité de la pièce jointe ne peut pas être garantie.');
        }

        $stream = $disk->readStream($attachment->file_path);
        if ($stream === false) {
            throw new RuntimeException('Impossible de lire la pièce jointe.');
        }
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);
        if (! hash_equals($attachment->sha256, hash_final($hash))) {
            throw new RuntimeException('L’intégrité de la pièce jointe ne peut pas être garantie.');
        }

        FormAttachmentDownloaded::dispatch($attachment->uuid, $form->uuid, $actor->uuid);

        return new AttachmentDownloadDTO(
            disk: $attachment->disk,
            path: $attachment->file_path,
            filename: $attachment->download_name,
            mimeType: $attachment->mime_type,
        );
    }

    public function srv_delete(
        MissingFormModel $form,
        FormAttachmentModel $attachment,
        UserModel $actor,
    ): void {
        $this->assertBelongsToForm($form, $attachment);
        $this->assertPrivateDisk($attachment->disk);
        $this->assertFormIsWritable($form);
        $this->assertCanDelete($form, $attachment, $actor);

        DB::transaction(function () use ($attachment, $actor): void {
            $this->attachments->repo_softDelete($attachment, $actor->user_code_unique);

            $disk = Storage::disk($attachment->disk);
            if ($disk->exists($attachment->file_path) && ! $disk->delete($attachment->file_path)) {
                throw new RuntimeException('Impossible de supprimer le fichier physique de la pièce jointe.');
            }
        });

        FormAttachmentDeleted::dispatch($attachment->uuid, $form->uuid, $actor->uuid);
    }

    private function assertCanAdd(MissingFormModel $form, UserModel $actor): void
    {
        if ($actor->can(PermissionCode::AttachmentsAddAny->value)) {
            return;
        }
        if ($actor->can(PermissionCode::AttachmentsAddOwn->value)
            && $this->attachments->repo_userIsAssignedToForm($form, $actor->user_code_unique)) {
            return;
        }

        throw new AuthorizationException('Vous ne pouvez pas ajouter une pièce jointe à ce dossier.');
    }

    private function assertCanDelete(
        MissingFormModel $form,
        FormAttachmentModel $attachment,
        UserModel $actor,
    ): void {
        if ($actor->can(PermissionCode::AttachmentsDeleteAny->value)) {
            return;
        }
        if ($actor->can(PermissionCode::AttachmentsDeleteOwn->value)
            && $attachment->uploaded_by_user_code_unique === $actor->user_code_unique
            && $this->attachments->repo_userIsAssignedToForm($form, $actor->user_code_unique)) {
            return;
        }

        throw new AuthorizationException('Vous ne pouvez pas supprimer cette pièce jointe.');
    }

    private function assertBelongsToForm(
        MissingFormModel $form,
        FormAttachmentModel $attachment,
    ): void {
        if ($attachment->missing_form_code_unique !== $form->missing_form_code_unique) {
            throw new AuthorizationException('Cette pièce jointe n’appartient pas au dossier demandé.');
        }
    }

    private function assertFormIsWritable(MissingFormModel $form): void
    {
        if ($form->status === FormStatus::Archived) {
            throw new AuthorizationException('Les pièces jointes d’un dossier archivé ne peuvent pas être modifiées.');
        }
    }

    private function detectedMimeType(string $path): string
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($path);

        if (! is_string($mimeType) || $mimeType === '') {
            throw ValidationException::withMessages([
                'attachment' => 'Le type réel de la pièce jointe ne peut pas être déterminé.',
            ]);
        }

        return strtolower($mimeType);
    }

    private function verifiedSize(string $path): int
    {
        $size = filesize($path);
        if ($size === false || $size <= 0) {
            throw ValidationException::withMessages([
                'attachment' => 'La pièce jointe est vide ou illisible.',
            ]);
        }

        return $size;
    }

    private function assertFileIsAllowed(string $extension, string $mimeType, int $sizeBytes): void
    {
        $allowedExtensions = array_map(
            'strtolower',
            (array) config('risfm.attachments.allowed_extensions', []),
        );
        $allowedMimeTypes = array_map(
            'strtolower',
            (array) config('risfm.attachments.allowed_mime_types', []),
        );
        $maxBytes = (int) config('risfm.attachments.max_size_kb', 10240) * 1024;

        if (! in_array($extension, $allowedExtensions, true)
            || ! in_array($mimeType, $allowedMimeTypes, true)) {
            throw ValidationException::withMessages([
                'attachment' => 'La pièce jointe doit être un fichier PDF, JPG, JPEG ou PNG valide.',
            ]);
        }
        if ($sizeBytes > $maxBytes) {
            throw ValidationException::withMessages([
                'attachment' => 'La pièce jointe dépasse la taille maximale autorisée.',
            ]);
        }
    }

    private function safeOriginalName(string $name, string $extension): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = trim($name, " .\t\n\r\0\x0B");

        return Str::limit($name !== '' ? $name : "piece-jointe.{$extension}", 255, '');
    }

    private function safeDownloadName(string $originalName, string $extension): string
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = Str::slug(Str::ascii($baseName), '-');

        $baseName = Str::limit($baseName !== '' ? $baseName : 'piece-jointe', 240, '');

        return "{$baseName}.{$extension}";
    }

    private function assertPrivateDisk(string $disk): void
    {
        $configuration = config("filesystems.disks.{$disk}");
        $root = is_array($configuration) ? ($configuration['root'] ?? null) : null;

        if (! is_array($configuration)
            || $disk === 'public'
            || ($configuration['visibility'] ?? null) === 'public'
            || (is_string($root) && str_starts_with($root, storage_path('app/public')))
            || (is_string($root) && str_starts_with($root, public_path()))) {
            throw new RuntimeException('Le disque des pièces jointes doit utiliser un stockage privé.');
        }
    }
}
