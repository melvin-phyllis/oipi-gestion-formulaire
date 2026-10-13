@extends('layouts.app')

@section('title', 'Registre des formulaires manquants')

@section('content')
    <!-- EN-TETE DE PAGE -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <h1 class="page-title">Formulaires manquants</h1>
                <div class="text-secondary mt-1">Consultez, recherchez et suivez le parcours de résolution des dossiers de propriété intellectuelle.</div>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex flex-wrap gap-2">
                @can('registry_import')
                    <button
                        type="button"
                        class="btn btn-outline-secondary d-inline-flex align-items-center"
                        data-bs-toggle="modal"
                        data-bs-target="#modal-import-missing-forms"
                        id="btn-open-import-modal"
                    >
                        <i class="ti ti-file-import me-1" aria-hidden="true"></i>
                        Importer un lot
                    </button>
                @endcan
                @can('registry_create')
                    <a class="btn btn-primary d-none d-sm-inline-flex align-items-center" href="{{ route('registry.forms.create') }}">
                        <i class="ti ti-file-plus me-1" aria-hidden="true"></i>
                        Nouveau formulaire manquant
                    </a>
                    <a class="btn btn-primary d-sm-none btn-icon" href="{{ route('registry.forms.create') }}" aria-label="Nouveau formulaire manquant" title="Nouveau formulaire manquant">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                    </a>
                @endcan
            </div>
        </div>
    </div>

    @if ($errors->has('import_file'))
        <div class="alert alert-danger alert-dismissible mb-3" role="alert">
            <div class="d-flex align-items-start">
                <i class="ti ti-alert-triangle fs-2 me-2 text-danger" aria-hidden="true"></i>
                <div class="flex-fill">
                    <h4 class="alert-title mb-1">Erreur lors de l’importation du fichier</h4>
                    <div class="text-secondary small mb-2">L’importation est atomique et a été annulée. Veuillez corriger les anomalies ci-dessous :</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->get('import_file') as $importError)
                            <li>{{ $importError }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @include('registry.forms._import_progress')

    <!-- FILTRES DE RECHERCHE -->
    <div class="card mb-3">
        <div class="card-header py-2">
            <h3 class="card-title fs-5">
                <i class="ti ti-filter me-2 text-primary" aria-hidden="true"></i>
                Filtrer les dossiers du registre
            </h3>
        </div>
        <div class="card-body py-3">
            <form method="get" action="{{ route('registry.forms.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-lg-3">
                        <label class="form-label mb-1" for="search">Recherche libre</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search text-secondary"></i></span>
                            <input
                                class="form-control"
                                id="search"
                                name="search"
                                type="search"
                                value="{{ request('search') }}"
                                placeholder="N° registre ou n° formulaire..."
                            >
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label mb-1" for="title_type_filter">Type de titre</label>
                        <select class="form-select" id="title_type_filter" name="title_type_code_unique">
                            <option value="">Tous les types</option>
                            @foreach ($titleTypes as $titleType)
                                <option value="{{ $titleType->title_type_code_unique }}" @selected(request('title_type_code_unique') === $titleType->title_type_code_unique)>
                                    {{ $titleType->title_type_code }} - {{ $titleType->title_type_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label mb-1" for="form_year">Année</label>
                        <input
                            class="form-control"
                            id="form_year"
                            name="form_year"
                            type="number"
                            min="1900"
                            max="{{ date('Y') }}"
                            value="{{ request('form_year') }}"
                            placeholder="ex. {{ date('Y') }}"
                        >
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label mb-1" for="status_filter">Statut</label>
                        <select class="form-select" id="status_filter" name="status">
                            <option value="">Tous les statuts</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label mb-1" for="priority_filter">Priorité</label>
                        <select class="form-select" id="priority_filter" name="priority">
                            <option value="">Toutes priorités</option>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>
                                    {{ $priority->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-1 d-flex gap-2">
                        <button class="btn btn-primary flex-fill" type="submit" title="Filtrer">
                            <i class="ti ti-check" aria-hidden="true"></i>
                        </button>
                        @if (request()->hasAny(['search', 'title_type_code_unique', 'form_year', 'status', 'priority']))
                            <a class="btn btn-outline-secondary" href="{{ route('registry.forms.index') }}" title="Réinitialiser">
                                <i class="ti ti-x" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLEAU DU REGISTRE -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>N° Registre</th>
                        <th>Type & Année</th>
                        <th>N° Formulaire</th>
                        <th>Priorité</th>
                        <th>Statut</th>
                        <th class="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($forms as $form)
                        <tr>
                            <td>
                                <a href="{{ route('registry.forms.show', $form) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                                    {{ $form->registry_number }}
                                </a>
                                @if ($form->tracking_cycle > 1)
                                    <span class="badge bg-purple-lt ms-1" title="Cycle de recherche n°{{ $form->tracking_cycle }}">
                                        Cycle {{ $form->tracking_cycle }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-lt text-primary fw-bold">{{ $form->titleType?->title_type_code }}</span>
                                    <span class="text-secondary small">{{ $form->form_year }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $form->form_number }}</div>
                            </td>
                            <td>
                                @if ($form->priority->value === 'urgent')
                                    <span class="badge bg-red text-white"><i class="ti ti-flame me-1"></i>Urgente</span>
                                @elseif ($form->priority->value === 'high')
                                    <span class="badge bg-orange text-white">Haute</span>
                                @elseif ($form->priority->value === 'normal')
                                    <span class="badge bg-secondary-lt text-secondary">Normale</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">Basse</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $form->status->color() }}-lt text-{{ $form->status->color() }}">
                                    <span class="badge badge-dot bg-{{ $form->status->color() }} me-1"></span>
                                    {{ $form->status->label() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-list flex-nowrap justify-content-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('registry.forms.show', $form) }}">
                                        <i class="ti ti-eye me-1" aria-hidden="true"></i>
                                        Consulter
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-action dropdown-toggle align-text-top" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            @can('registry_update_metadata')
                                                <a class="dropdown-item" href="{{ route('registry.forms.edit', $form) }}">
                                                    <i class="ti ti-edit me-2 dropdown-item-icon text-primary"></i>
                                                    Modifier les métadonnées
                                                </a>
                                            @endcan

                                            @can('registry_archive')
                                                @if ($form->status->value === 'archived')
                                                    <form method="post" action="{{ route('registry.forms.restore', $form) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="dropdown-item text-success" type="submit">
                                                            <i class="ti ti-rotate-rectangle me-2 dropdown-item-icon text-success"></i>
                                                            Restaurer le dossier
                                                        </button>
                                                    </form>
                                                @elseif (! $form->has_active_missions)
                                                    <button class="dropdown-item text-danger" type="button" data-bs-toggle="modal" data-bs-target="#modal-archive-{{ $form->uuid }}">
                                                        <i class="ti ti-archive me-2 dropdown-item-icon text-danger"></i>
                                                        Archiver le dossier
                                                    </button>
                                                @endif
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        @can('registry_archive')
                            @if ($form->status->value !== 'archived' && ! $form->has_active_missions)
                                @push('modals')
                                    <!-- MODALE D'ARCHIVAGE DU DOSSIER -->
                                    <div class="modal modal-blur fade" id="modal-archive-{{ $form->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <form class="modal-content" method="post" action="{{ route('registry.forms.archive', $form) }}">
                                                @csrf
                                                @method('PATCH')
                                                <div class="modal-status bg-danger"></div>
                                                <div class="modal-header">
                                                    <h3 class="modal-title">
                                                        <i class="ti ti-archive me-2 text-danger"></i>
                                                        Archiver le formulaire {{ $form->registry_number }}
                                                    </h3>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-secondary small mb-3">
                                                        L'archivage suspend le suivi de ce dossier. Vous devez obligatoirement préciser un motif. Note : un formulaire ayant une mission en cours ne peut pas être archivé.
                                                    </p>
                                                    <div class="mb-3">
                                                        <label class="form-label required" for="archive_reason_{{ $form->uuid }}">Motif de l'archivage</label>
                                                        <textarea
                                                            class="form-control"
                                                            id="archive_reason_{{ $form->uuid }}"
                                                            name="archive_reason"
                                                            rows="3"
                                                            maxlength="500"
                                                            placeholder="Indiquez la raison de l'archivage..."
                                                            required
                                                        ></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-danger">Confirmer l'archivage</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endpush
                            @endif
                        @endcan
                    @empty
                        <tr>
                            <td class="text-center text-secondary py-5" colspan="6">
                                <div class="empty">
                                    <div class="empty-icon text-muted">
                                        <i class="ti ti-file-search fs-1"></i>
                                    </div>
                                    <p class="empty-title">Aucun formulaire manquant trouvé</p>
                                    <p class="empty-subtitle text-secondary">
                                        Ajustez vos filtres ou enregistrez un nouveau formulaire à rechercher.
                                    </p>
                                    @can('registry_create')
                                        <div class="empty-action">
                                            <a href="{{ route('registry.forms.create') }}" class="btn btn-primary">
                                                <i class="ti ti-plus me-1"></i>
                                                Ajouter un formulaire
                                            </a>
                                        </div>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($forms->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $forms->links() }}
            </div>
        @endif
    </div>

    @include('registry.forms._import_modal')
@endsection

