<?php

namespace App\Repositories\Interfaces\Registry;

use App\Dto\Registry\CreateFormAttachmentDTO;
use App\Models\Registry\FormAttachmentModel;
use App\Models\Registry\MissingFormModel;

interface FormAttachmentRepositoryInterface
{
    public function repo_create(CreateFormAttachmentDTO $data): FormAttachmentModel;

    public function repo_userIsAssignedToForm(
        MissingFormModel $form,
        string $userCodeUnique,
    ): bool;

    public function repo_softDelete(
        FormAttachmentModel $attachment,
        string $actorCodeUnique,
    ): void;
}
