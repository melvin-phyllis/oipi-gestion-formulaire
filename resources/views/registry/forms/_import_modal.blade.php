@can('registry_import')
    @push('modals')
        <div
            class="modal modal-blur fade"
            id="modal-import-missing-forms"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modal-import-missing-forms-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <form
                    method="post"
                    action="{{ route('registry.forms.import') }}"
                    enctype="multipart/form-data"
                    class="modal-content"
                    novalidate
                >
                    @csrf
                    <div class="modal-header py-3">
                        <h5 class="modal-title d-flex align-items-center" id="modal-import-missing-forms-title">
                            <i class="ti ti-file-import text-primary me-2 fs-2" aria-hidden="true"></i>
                            Importer des formulaires manquants
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body py-3">
                        <div class="p-3 rounded border bg-light mb-3">
                            <div class="fw-semibold text-dark mb-2">
                                <i class="ti ti-file-description text-primary me-1" aria-hidden="true"></i>
                                Règles et format d’importation
                            </div>
                            <ul class="text-secondary small mb-0 ps-3">
                                <li>Formats acceptés : <strong>CSV</strong> (séparateur <code>;</code> ou <code>,</code>) et <strong>XLSX</strong>.</li>
                                <li>Colonnes attendues : <code>Type de titre | Année | Numéro du formulaire | Priorité</code>.</li>
                                <li>Le type de titre accepte le code (ex. <em>BRV</em>, <em>MAQ</em>) ou son libellé.</li>
                                <li>La priorité est facultative et vaut <strong>Normale</strong> par défaut.</li>
                                <li>Taille maximale : <strong>20 Mo</strong> (limité à <strong>100 000 formulaires</strong> par lot).</li>
                                <li>L'importation est <strong>entièrement atomique</strong> : la moindre erreur annule l'intégralité du lot.</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required" for="import_file">Fichier d’importation</label>
                            <input
                                type="file"
                                class="form-control @error('import_file') is-invalid @enderror"
                                id="import_file"
                                name="import_file"
                                accept=".csv,.xlsx,.txt,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
                                required
                            >
                            <div class="form-hint small text-secondary mt-1">
                                Choisissez un fichier CSV ou Excel (.xlsx) contenant les formulaires à intégrer.
                            </div>
                            @error('import_file')
                                <div class="invalid-feedback d-block mt-2">
                                    @if (count($errors->get('import_file')) > 1)
                                        <ul class="mb-0 ps-3">
                                            @foreach ($errors->get('import_file') as $err)
                                                <li>{{ $err }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        {{ $message }}
                                    @endif
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer py-2 d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Annuler
                        </button>
                        <button type="submit" class="btn btn-primary" id="btn-submit-import">
                            <i class="ti ti-upload me-1" aria-hidden="true"></i>
                            Importer le fichier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endpush

    @if ($errors->has('import_file'))
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const modalEl = document.getElementById('modal-import-missing-forms');
                    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                        const modal = new window.bootstrap.Modal(modalEl);
                        modal.show();
                    }
                });
            </script>
        @endpush
    @endif
@endcan

