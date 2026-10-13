<?php

namespace App\Http\Controllers\Registry;

use App\Dto\Registry\ArchiveMissingFormDTO;
use App\Dto\Registry\ConfirmFinalizationDTO;
use App\Dto\Registry\CreateMissingFormDTO;
use App\Dto\Registry\ImportMissingFormsDTO;
use App\Dto\Registry\MissingFormFiltersDTO;
use App\Dto\Registry\RegisterFoundMissingFormDTO;
use App\Dto\Registry\ReopenMissingFormDTO;
use App\Dto\Registry\UpdateMissingFormDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Registry\ArchiveMissingFormFormRequest;
use App\Http\Requests\Registry\ConfirmFinalizationFormRequest;
use App\Http\Requests\Registry\CreateMissingFormFormRequest;
use App\Http\Requests\Registry\ImportMissingFormsFormRequest;
use App\Http\Requests\Registry\MissingFormFiltersFormRequest;
use App\Http\Requests\Registry\RegisterFoundMissingFormFormRequest;
use App\Http\Requests\Registry\ReopenMissingFormFormRequest;
use App\Http\Requests\Registry\RestoreMissingFormFormRequest;
use App\Http\Requests\Registry\UpdateMissingFormFormRequest;
use App\Http\Requests\Registry\ViewMissingFormImportFormRequest;
use App\Models\Registry\MissingFormImportModel;
use App\Models\Registry\MissingFormModel;
use App\Services\Registry\DirectFoundRegistrationService;
use App\Services\Registry\FormFinalizationService;
use App\Services\Registry\FormReopeningService;
use App\Services\Registry\MissingFormService;
use App\Services\Registry\QueuedMissingFormImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

final class MissingFormController extends Controller
{
    public function __construct(
        private readonly MissingFormService $forms,
        private readonly DirectFoundRegistrationService $directFoundRegistrations,
        private readonly FormFinalizationService $finalizations,
        private readonly FormReopeningService $reopenings,
        private readonly QueuedMissingFormImportService $imports,
    ) {}

    public function ctrl_index(MissingFormFiltersFormRequest $request): View
    {
        return view(
            'registry.forms.index',
            $this->forms->srv_list(
                MissingFormFiltersDTO::fromValidated($request->validated()),
            ),
        );
    }

    public function ctrl_create(): View
    {
        return view('registry.forms.create', $this->forms->srv_createData());
    }

    public function ctrl_show(MissingFormModel $missingForm): View
    {
        return view('registry.forms.show', $this->forms->srv_showData($missingForm));
    }

    public function ctrl_edit(MissingFormModel $missingForm): View
    {
        return view('registry.forms.edit', $this->forms->srv_editData($missingForm));
    }

    public function ctrl_store(CreateMissingFormFormRequest $request): RedirectResponse
    {
        $form = $this->forms->srv_create(
            CreateMissingFormDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()
            ->with('success', "Le formulaire {$form->registry_number} a été ajouté au registre.")
            ->with('created_missing_form_uuid', $form->uuid);
    }

    public function ctrl_import(ImportMissingFormsFormRequest $request): RedirectResponse
    {
        $file = $request->file('import_file');

        if (! $file instanceof UploadedFile) {
            abort(422, 'Le fichier d’import est invalide.');
        }

        $import = $this->imports->srv_queue(
            new ImportMissingFormsDTO(
                filePath: $file->getPathname(),
                originalFileName: $file->getClientOriginalName(),
                extension: $file->getClientOriginalExtension(),
                sizeBytes: (int) $file->getSize(),
            ),
            $request->user(),
        );

        return redirect()
            ->route('registry.forms.index')
            ->with('success', 'Le fichier a été accepté. Son import est maintenant traité en arrière-plan.')
            ->with('missing_form_import_uuid', $import->uuid);
    }

    public function ctrl_importStatus(
        ViewMissingFormImportFormRequest $request,
        MissingFormImportModel $missingFormImport,
    ): JsonResponse {
        $totalRows = max(0, $missingFormImport->total_rows);
        $progress = $missingFormImport->status->value === 'completed'
            ? 100
            : ($totalRows > 0
                ? min(99, (int) floor(($missingFormImport->processed_rows / $totalRows) * 100))
                : 0);

        return response()->json([
            'data' => [
                'uuid' => $missingFormImport->uuid,
                'status' => $missingFormImport->status->value,
                'original_file_name' => $missingFormImport->original_file_name,
                'total_rows' => $missingFormImport->total_rows,
                'processed_rows' => $missingFormImport->processed_rows,
                'imported_rows' => $missingFormImport->imported_rows,
                'error_count' => $missingFormImport->error_count,
                'errors' => $missingFormImport->errors ?? [],
                'progress' => $progress,
            ],
        ]);
    }

    public function ctrl_storeFound(RegisterFoundMissingFormFormRequest $request): RedirectResponse
    {
        $form = $this->directFoundRegistrations->srv_register(
            RegisterFoundMissingFormDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return redirect()
            ->route('registry.forms.show', $form)
            ->with('success', "Le formulaire {$form->registry_number} a été enregistré comme retrouvé.");
    }

    public function ctrl_update(
        UpdateMissingFormFormRequest $request,
        MissingFormModel $missingForm,
    ): RedirectResponse {
        $this->forms->srv_updateMetadata(
            $missingForm,
            UpdateMissingFormDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with('success', 'Les informations du formulaire ont été mises à jour.');
    }

    public function ctrl_archive(
        ArchiveMissingFormFormRequest $request,
        MissingFormModel $missingForm,
    ): RedirectResponse {
        $this->forms->srv_archive(
            $missingForm,
            ArchiveMissingFormDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with('success', 'Le formulaire a été archivé.');
    }

    public function ctrl_restore(
        RestoreMissingFormFormRequest $request,
        MissingFormModel $missingForm,
    ): RedirectResponse {
        $this->forms->srv_restore($missingForm, $request->user());

        return back()->with('success', 'Le formulaire a été restauré.');
    }

    public function ctrl_confirmDigitized(
        ConfirmFinalizationFormRequest $request,
        MissingFormModel $missingForm,
    ): RedirectResponse {
        $this->finalizations->srv_confirmDigitized(
            $missingForm,
            ConfirmFinalizationDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with('success', 'La numérisation du formulaire a été confirmée.');
    }

    public function ctrl_confirmEntered(
        ConfirmFinalizationFormRequest $request,
        MissingFormModel $missingForm,
    ): RedirectResponse {
        $this->finalizations->srv_confirmEntered(
            $missingForm,
            ConfirmFinalizationDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with('success', 'La saisie du formulaire a été confirmée.');
    }

    public function ctrl_reopen(
        ReopenMissingFormFormRequest $request,
        MissingFormModel $missingForm,
    ): RedirectResponse {
        $this->reopenings->srv_reopen(
            $missingForm,
            ReopenMissingFormDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with('success', 'Le formulaire a été rouvert dans un nouveau cycle de recherche.');
    }
}
