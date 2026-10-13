<?php

namespace App\Http\Controllers\Registry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registry\DeleteFormAttachmentFormRequest;
use App\Http\Requests\Registry\DownloadFormAttachmentFormRequest;
use App\Http\Requests\Registry\UploadFormAttachmentFormRequest;
use App\Models\Registry\FormAttachmentModel;
use App\Models\Registry\MissingFormModel;
use App\Services\Registry\FormAttachmentService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FormAttachmentController extends Controller
{
    public function __construct(private readonly FormAttachmentService $attachments) {}

    public function ctrl_store(
        UploadFormAttachmentFormRequest $request,
        MissingFormModel $missingForm,
    ): RedirectResponse {
        $this->attachments->srv_add($missingForm, $request->toDto(), $request->user());

        return back()->with('success', 'La pièce jointe a été ajoutée au dossier.');
    }

    public function ctrl_download(
        DownloadFormAttachmentFormRequest $request,
        MissingFormModel $missingForm,
        FormAttachmentModel $attachment,
    ): StreamedResponse {
        return $this->attachments->srv_download($missingForm, $attachment, $request->user());
    }

    public function ctrl_destroy(
        DeleteFormAttachmentFormRequest $request,
        MissingFormModel $missingForm,
        FormAttachmentModel $attachment,
    ): RedirectResponse {
        $this->attachments->srv_delete($missingForm, $attachment, $request->user());

        return back()->with('success', 'La pièce jointe a été supprimée du dossier.');
    }
}
