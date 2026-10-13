<?php

namespace App\Repositories\Registry;

use App\Dto\Registry\CreateFormAttachmentDTO;
use App\Enums\Mission\MissionStatus;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\FormAttachmentModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Registry\FormAttachmentRepositoryInterface;

final class FormAttachmentRepository implements FormAttachmentRepositoryInterface
{
    public function repo_create(CreateFormAttachmentDTO $data): FormAttachmentModel
    {
        return FormAttachmentModel::query()->create([
            'uuid' => $data->uuid,
            'missing_form_code_unique' => $data->missingFormCodeUnique,
            'disk' => $data->disk,
            'file_path' => $data->filePath,
            'stored_name' => $data->storedName,
            'original_name' => $data->originalName,
            'download_name' => $data->downloadName,
            'mime_type' => $data->mimeType,
            'extension' => $data->extension,
            'size_bytes' => $data->sizeBytes,
            'sha256' => $data->sha256,
            'uploaded_by_user_code_unique' => $data->uploadedByUserCodeUnique,
            'uploader_name' => $data->uploaderName,
        ]);
    }

    public function repo_userIsAssignedToForm(
        MissingFormModel $form,
        string $userCodeUnique,
    ): bool {
        if ($form->last_responsible_user_code_unique === $userCodeUnique) {
            return true;
        }

        return SearchMissionModel::query()
            ->where('missing_form_code_unique', $form->missing_form_code_unique)
            ->where('tracking_cycle', $form->tracking_cycle)
            ->where('responsible_user_code_unique', $userCodeUnique)
            ->whereIn('status', [MissionStatus::Assigned->value, MissionStatus::InProgress->value])
            ->exists();
    }

    public function repo_softDelete(
        FormAttachmentModel $attachment,
        string $actorCodeUnique,
    ): void {
        $attachment->deleted_by_user_code_unique = $actorCodeUnique;
        $attachment->save();
        $attachment->delete();
    }
}
