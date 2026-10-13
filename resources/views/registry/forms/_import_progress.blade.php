@if (session('missing_form_import_uuid'))
    <div
        class="alert alert-info mb-3"
        role="status"
        aria-live="polite"
        data-registry-import-progress
        data-import-uuid="{{ session('missing_form_import_uuid') }}"
        data-status-url="{{ route('registry.forms.imports.status', session('missing_form_import_uuid')) }}"
    >
        <div class="d-flex align-items-start gap-3">
            <div class="spinner-border spinner-border-sm mt-1" data-import-spinner aria-hidden="true"></div>
            <div class="flex-fill">
                <div class="fw-semibold" data-import-title>Import en attente de traitement</div>
                <div class="small text-secondary mt-1" data-import-message>
                    Le fichier a été accepté et sera traité par le worker d’import.
                </div>
                <div class="progress progress-sm mt-2" aria-label="Progression de l’importation">
                    <div
                        class="progress-bar"
                        data-import-progress-bar
                        role="progressbar"
                        style="width: 0%"
                        aria-valuenow="0"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>
                <div class="small text-secondary mt-1" data-import-progress-label>0 %</div>
                <ul class="small mb-0 mt-2 d-none" data-import-errors></ul>
            </div>
        </div>
    </div>
@endif
