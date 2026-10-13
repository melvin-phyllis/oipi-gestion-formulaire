@extends('layouts.app')

@section('title', 'Dossier ' . $missingForm->registry_number)

@section('content')
    @php
        $workflowStatus = $missingForm->status->value === 'archived'
            ? $missingForm->status_before_archive?->value
            : $missingForm->status->value;
        $currentUser = auth()->user();
        $isFormArchived = $missingForm->status->value === 'archived';
        $canAddAttachments = false;
        if (! $isFormArchived && $currentUser) {
            if ($currentUser->can('attachments_add_any')) {
                $canAddAttachments = true;
            } elseif ($currentUser->can('attachments_add_own')) {
                $canAddAttachments = ($missingForm->last_responsible_user_code_unique === $currentUser->user_code_unique)
                    || $missingForm->missions->contains(fn ($m) =>
                        $m->tracking_cycle === $missingForm->tracking_cycle
                        && $m->responsible_user_code_unique === $currentUser->user_code_unique
                        && $m->status->isActive()
                    );
            }
        }
    @endphp

    <!-- EN-TETE DE PAGE -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Registre / Détail du dossier</div>
                <h1 class="page-title d-flex flex-wrap align-items-center gap-2">
                    <span>{{ $missingForm->registry_number }}</span>
                    <span class="badge bg-{{ $missingForm->status->color() }}-lt text-{{ $missingForm->status->color() }} fs-6">
                        <span class="badge badge-dot bg-{{ $missingForm->status->color() }} me-1"></span>
                        {{ $missingForm->status->label() }}
                    </span>
                    @if ($missingForm->priority->value === 'urgent')
                        <span class="badge bg-red text-white fs-6"><i class="ti ti-flame me-1"></i>Urgente</span>
                    @elseif ($missingForm->priority->value === 'high')
                        <span class="badge bg-orange text-white fs-6">Haute</span>
                    @endif
                    @if ($missingForm->tracking_cycle > 1)
                        <span class="badge bg-purple text-white fs-6">Cycle de recherche {{ $missingForm->tracking_cycle }}</span>
                    @endif
                </h1>
                <div class="text-secondary mt-1">
                    {{ $missingForm->titleType?->title_type_name }} ({{ $missingForm->titleType?->title_type_code }}) · N° {{ $missingForm->form_number }} ({{ $missingForm->form_year }})
                </div>
            </div>

            <!-- BOUTONS D'ACTIONS HAUT DE PAGE -->
            <div class="col-auto ms-auto d-print-none d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('registry.forms.index') }}">
                    <i class="ti ti-arrow-left me-1"></i>
                    Retour au registre
                </a>

                @can('registry_update_metadata')
                    <a class="btn btn-outline-primary" href="{{ route('registry.forms.edit', $missingForm) }}">
                        <i class="ti ti-edit me-1"></i>
                        Modifier
                    </a>
                @endcan

                @can('missions_assign')
                    @if (in_array($missingForm->status->value, ['missing', 'in_search']))
                        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modal-assign-mission">
                            <i class="ti ti-user-plus me-1"></i>
                            Affecter une mission
                        </button>
                    @endif
                @endcan

                @can('registry_archive')
                    @if ($missingForm->status->value === 'archived')
                        <form method="post" action="{{ route('registry.forms.restore', $missingForm) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-success" type="submit">
                                <i class="ti ti-rotate-rectangle me-1"></i>
                                Restaurer le dossier
                            </button>
                        </form>
                    @elseif (! $hasActiveMissions)
                        <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#modal-archive-form">
                            <i class="ti ti-archive me-1"></i>
                            Archiver
                        </button>
                    @endif
                @endcan

                @can('registry_reopen')
                    @if (in_array($missingForm->status->value, ['found', 'digitized', 'entered'], true))
                        <button class="btn btn-outline-warning" type="button" data-bs-toggle="modal" data-bs-target="#modal-reopen-form">
                            <i class="ti ti-refresh me-1"></i>
                            Rouvrir le dossier
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    <!-- BANDEAU STEPPER TABLER POUR LE PARCOURS DE FINALISATION -->
    @if (in_array($workflowStatus, ['found', 'digitized', 'entered'], true))
        <div class="card mb-3">
            <div class="card-header py-2 bg-surface">
                <h3 class="card-title fs-5">
                    <i class="ti ti-progress-check me-2 text-primary"></i>
                    Parcours de finalisation du dossier
                </h3>
            </div>
            <div class="card-body py-3">
                <ul class="steps steps-green steps-counter my-2">
                    <li class="step-item{{ $workflowStatus === 'found' ? ' active' : '' }}" data-finalization-step="found">
                        Retrouvé
                    </li>
                    <li class="step-item{{ $workflowStatus === 'digitized' ? ' active' : '' }}" data-finalization-step="digitized">
                        Numérisé
                    </li>
                    <li class="step-item{{ $workflowStatus === 'entered' ? ' active' : '' }}" data-finalization-step="entered">
                        Saisi dans l'ERP
                    </li>
                </ul>

                @can('registry_finalize')
                    <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                        @if ($missingForm->status->value === 'found')
                            <form method="post" action="{{ route('registry.forms.finalization.digitized', $missingForm) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="performed_on" value="{{ date('Y-m-d') }}">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="ti ti-scan me-1"></i>
                                    Confirmer l'étape : Numérisé
                                </button>
                            </form>
                        @elseif ($missingForm->status->value === 'digitized')
                            <form method="post" action="{{ route('registry.forms.finalization.entered', $missingForm) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="performed_on" value="{{ date('Y-m-d') }}">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="ti ti-keyboard me-1"></i>
                                    Confirmer l'étape finale : Saisi
                                </button>
                            </form>
                        @endif
                    </div>
                @endcan
            </div>
        </div>
    @endif

    <div class="row g-3">
        <!-- COLONNE GAUCHE : METADONNEES DU FORMULAIRE -->
        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-header py-3">
                    <h3 class="card-title">
                        <i class="ti ti-file-text me-2 text-primary"></i>
                        Données administratives
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-secondary">N° Registre</dt>
                        <dd class="col-7 font-monospace fw-bold">{{ $missingForm->registry_number }}</dd>

                        <dt class="col-5 text-secondary">Type de titre</dt>
                        <dd class="col-7">{{ $missingForm->titleType?->title_type_name }} ({{ $missingForm->titleType?->title_type_code }})</dd>

                        <dt class="col-5 text-secondary">N° Formulaire</dt>
                        <dd class="col-7 fw-medium">{{ $missingForm->form_number }}</dd>

                        <dt class="col-5 text-secondary">Année</dt>
                        <dd class="col-7">{{ $missingForm->form_year }}</dd>

                        <dt class="col-5 text-secondary">Priorité</dt>
                        <dd class="col-7">
                            <span class="badge bg-secondary-lt">{{ $missingForm->priority->label() }}</span>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- CARTE RECAPITULATIF DE RECHERCHE -->
            <div class="card mb-3">
                <div class="card-header py-3">
                    <h3 class="card-title">
                        <i class="ti ti-map-pin me-2 text-primary"></i>
                        État des recherches
                    </h3>
                </div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-6 text-secondary">Cycle actuel</dt>
                        <dd class="col-6 text-end fw-bold">#{{ $missingForm->tracking_cycle }}</dd>

                        <dt class="col-6 text-secondary">Dernier lieu</dt>
                        <dd class="col-6 text-end">{{ $missingForm->lastSearchLocation?->location_name ?: 'Aucun' }}</dd>

                        @if ($missingForm->lastResponsible)
                            <dt class="col-6 text-secondary">Dernier agent</dt>
                            <dd class="col-6 text-end">{{ $missingForm->lastResponsible->first_names . ' ' . $missingForm->lastResponsible->last_name }}</dd>
                        @elseif ($missingForm->directFoundReport)
                            <dt class="col-6 text-secondary">Retrouvé par</dt>
                            <dd class="col-6 text-end fw-medium text-dark">{{ $missingForm->directFoundReport->finder_name }}</dd>
                        @else
                            <dt class="col-6 text-secondary">Dernier agent</dt>
                            <dd class="col-6 text-end">Non affecté</dd>
                        @endif

                        <dt class="col-6 text-secondary">Échéance</dt>
                        <dd class="col-6 text-end">{{ $missingForm->search_due_date ? $missingForm->search_due_date->format('d/m/Y') : '—' }}</dd>

                        <dt class="col-6 text-secondary">Résolu le</dt>
                        <dd class="col-6 text-end">{{ $missingForm->resolved_at ? $missingForm->resolved_at->format('d/m/Y') : 'En attente' }}</dd>
                    </dl>

                    @if ($missingForm->result_summary)
                        <div class="mt-3 pt-2 border-top">
                            <div class="text-secondary fw-semibold mb-1">Dernier compte rendu :</div>
                            <div class="bg-surface p-2 rounded text-dark fst-italic">
                                « {{ $missingForm->result_summary }} »
                            </div>
                        </div>
                    @endif

                    @if ($missingForm->archive_reason)
                        <div class="mt-3 pt-2 border-top">
                            <div class="text-danger fw-semibold mb-1">Motif d'archivage :</div>
                            <div class="bg-red-lt p-2 rounded text-danger">
                                {{ $missingForm->archive_reason }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- CARTE DECLARATION DE DECOUVERTE DIRECTE -->
            @if ($missingForm->directFoundReport)
                @php
                    $directReport = $missingForm->directFoundReport;
                @endphp
                <div class="card mb-3">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">
                            <i class="ti ti-circle-check me-2 text-primary"></i>
                            Découverte directe
                        </h3>
                        <span class="badge bg-green-lt text-green font-monospace">
                            {{ $directReport->direct_found_report_code_unique }}
                        </span>
                    </div>
                    <div class="card-body small">
                        <dl class="row mb-0">
                            <dt class="col-5 text-secondary">Profil</dt>
                            <dd class="col-7 text-end">
                                <span class="badge {{ $directReport->finder_type->value === 'internal' ? 'bg-primary-lt text-primary' : 'bg-orange-lt text-orange' }}">
                                    {{ $directReport->finder_type->label() }}
                                </span>
                            </dd>

                            <dt class="col-5 text-secondary">Retrouvé par</dt>
                            <dd class="col-7 text-end fw-semibold text-dark">{{ $directReport->finder_name }}</dd>

                            @if ($directReport->finder_organization)
                                <dt class="col-5 text-secondary">Organisme / Service</dt>
                                <dd class="col-7 text-end">{{ $directReport->finder_organization }}</dd>
                            @endif

                            @if ($directReport->finder_contact)
                                <dt class="col-5 text-secondary">Contact</dt>
                                <dd class="col-7 text-end">{{ $directReport->finder_contact }}</dd>
                            @endif

                            <dt class="col-5 text-secondary">Localisation</dt>
                            <dd class="col-7 text-end">{{ $directReport->foundLocation?->location_name ?? $missingForm->lastSearchLocation?->location_name ?? '—' }}</dd>

                            <dt class="col-5 text-secondary">Date de découverte</dt>
                            <dd class="col-7 text-end fw-medium">{{ $directReport->found_on?->format('d/m/Y') ?? '—' }}</dd>

                            <dt class="col-5 text-secondary">Enregistré par</dt>
                            <dd class="col-7 text-end">{{ $directReport->recorded_by_name }}</dd>
                        </dl>
                    </div>
                </div>
            @endif
        </div>

        <!-- COLONNE DROITE : MISSIONS & HISTORIQUE -->
        <div class="col-12 col-xl-8">
            <!-- LISTE DES MISSIONS DE RECHERCHE -->
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="ti ti-target me-2 text-primary"></i>
                        Missions de recherche associées ({{ $missingForm->missions->count() }})
                    </h3>
                    @can('missions_assign')
                        @if (in_array($missingForm->status->value, ['missing', 'in_search']))
                            <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modal-assign-mission">
                                <i class="ti ti-plus me-1"></i>
                                Nouvelle mission
                            </button>
                        @endif
                    @endcan
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Agent responsable</th>
                                <th>Localisation</th>
                                <th>Échéance</th>
                                <th>État</th>
                                <th>Résultat</th>
                                <th class="w-1 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($missingForm->missions as $mission)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $mission->responsible?->first_names }} {{ $mission->responsible?->last_name }}</div>
                                        <div class="text-secondary small">{{ $mission->responsible?->email }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border">
                                            {{ $mission->searchLocation?->location_name ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($mission->due_date)
                                            <span class="{{ $mission->due_date->isPast() && $mission->status->isActive() ? 'text-danger fw-bold' : '' }}">
                                                {{ $mission->due_date->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-secondary">Sans échéance</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($mission->status->value === 'assigned')
                                            <span class="badge bg-yellow-lt">Affectée</span>
                                        @elseif ($mission->status->value === 'in_progress')
                                            <span class="badge bg-warning-lt text-warning">En cours</span>
                                        @elseif ($mission->status->value === 'completed')
                                            <span class="badge bg-green-lt">Terminée</span>
                                        @elseif ($mission->status->value === 'cancelled')
                                            <span class="badge bg-secondary-lt">Annulée</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($mission->result?->value === 'found')
                                            <span class="badge bg-success text-white">Retrouvé</span>
                                        @elseif ($mission->result?->value === 'needs_verification')
                                            <span class="badge bg-warning text-dark">À vérifier</span>
                                        @elseif ($mission->result?->value === 'not_found')
                                            <span class="badge bg-danger text-white">Non retrouvé</span>
                                        @else
                                            <span class="text-secondary">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('missions.show', $mission) }}" class="btn btn-sm btn-outline-primary">
                                            Voir
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-secondary py-4" colspan="6">
                                        @if ($missingForm->directFoundReport)
                                            Aucune mission de recherche n'a été créée : ce formulaire a été enregistré directement comme retrouvé.
                                        @else
                                            Aucune mission de recherche affectée pour ce dossier.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PIECES JOINTES ASSOCIEES AU DOSSIER -->
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="ti ti-paperclip me-2 text-primary"></i>
                        Pièces jointes associées ({{ $missingForm->attachments->count() }})
                    </h3>
                    @if ($canAddAttachments)
                        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modal-add-attachment">
                            <i class="ti ti-upload me-1"></i>
                            Ajouter une pièce jointe
                        </button>
                    @elseif ($isFormArchived)
                        <span class="badge bg-secondary-lt">Dossier archivé (lecture seule)</span>
                    @endif
                </div>

                @if ($missingForm->attachments->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            <thead>
                                <tr>
                                    <th style="min-width: 40px;" class="w-1">Type</th>
                                    <th style="min-width: 200px;">Nom de fichier</th>
                                    <th style="min-width: 90px;">Taille</th>
                                    <th style="min-width: 150px;">Ajouté par</th>
                                    <th style="min-width: 130px;">Date d'ajout</th>
                                    <th class="w-1 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($missingForm->attachments as $attachment)
                                    @php
                                        $isPdf = strtolower($attachment->extension) === 'pdf';
                                        $fileIconClass = $isPdf ? 'ti ti-file-type-pdf text-danger' : 'ti ti-photo text-success';

                                        $canDownload = true;
                                        if ($isFormArchived && ! $currentUser?->can('registry_archive')) {
                                            $canDownload = false;
                                        }

                                        $canDelete = false;
                                        if (! $isFormArchived && $currentUser) {
                                            if ($currentUser->can('attachments_delete_any')) {
                                                $canDelete = true;
                                            } elseif ($currentUser->can('attachments_delete_own') && $attachment->uploaded_by_user_code_unique === $currentUser->user_code_unique) {
                                                $canDelete = ($missingForm->last_responsible_user_code_unique === $currentUser->user_code_unique)
                                                    || $missingForm->missions->contains(fn ($m) =>
                                                        $m->tracking_cycle === $missingForm->tracking_cycle
                                                        && $m->responsible_user_code_unique === $currentUser->user_code_unique
                                                        && $m->status->isActive()
                                                    );
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <!-- ICONE TYPE -->
                                        <td class="text-center">
                                            <i class="{{ $fileIconClass }} fs-2"></i>
                                        </td>

                                        <!-- NOM ORIGINAL ET CODE -->
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $attachment->original_name }}</div>
                                            <div class="text-secondary small font-monospace">
                                                <span class="badge bg-light text-secondary font-monospace small me-1">{{ $attachment->form_attachment_code_unique }}</span>
                                                {{ strtoupper($attachment->extension) }}
                                            </div>
                                        </td>

                                        <!-- TAILLE -->
                                        <td class="text-nowrap font-monospace text-secondary small">
                                            @if ($attachment->size_bytes < 1048576)
                                                {{ number_format($attachment->size_bytes / 1024, 1, ',', ' ') }} Ko
                                            @else
                                                {{ number_format($attachment->size_bytes / 1048576, 2, ',', ' ') }} Mo
                                            @endif
                                        </td>

                                        <!-- UPLOADER -->
                                        <td>
                                            <div class="text-dark small fw-medium">
                                                {{ $attachment->uploader_name ?: ($attachment->uploadedBy?->first_names . ' ' . $attachment->uploadedBy?->last_name) ?: 'Système' }}
                                            </div>
                                            @if ($attachment->uploaded_by_user_code_unique)
                                                <div class="text-secondary small font-monospace">{{ $attachment->uploaded_by_user_code_unique }}</div>
                                            @endif
                                        </td>

                                        <!-- DATE D'AJOUT -->
                                        <td class="text-nowrap small text-secondary">
                                            {{ $attachment->created_at?->format('d/m/Y H:i') }}
                                        </td>

                                        <!-- ACTIONS -->
                                        <td class="text-end text-nowrap">
                                            <div class="d-flex align-items-center justify-content-end gap-1">
                                                @if ($canDownload)
                                                    <a
                                                        href="{{ route('registry.forms.attachments.download', [$missingForm, $attachment]) }}"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        title="Télécharger la pièce jointe ({{ $attachment->download_name }})"
                                                    >
                                                        <i class="ti ti-download me-1"></i>Télécharger
                                                    </a>
                                                @else
                                                    <span class="badge bg-secondary-lt" title="Téléchargement restreint sur dossier archivé">
                                                        <i class="ti ti-lock me-1"></i>Verrouillé
                                                    </span>
                                                @endif

                                                @if ($canDelete)
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modal-delete-attachment-{{ $attachment->id }}"
                                                        title="Supprimer la pièce jointe"
                                                    >
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body text-center py-4 text-secondary">
                        <i class="ti ti-paperclip-off fs-1 d-block mb-2 text-muted"></i>
                        <div>Aucune pièce jointe n'a été ajoutée à ce dossier.</div>
                        @if ($canAddAttachments)
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modal-add-attachment">
                                    <i class="ti ti-upload me-1"></i>
                                    Téléverser le premier document
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- JOURNAL DE L'HISTORIQUE DES ACTIONS -->
            <div class="card">
                <div class="card-header py-3">
                    <h3 class="card-title">
                        <i class="ti ti-history me-2 text-primary"></i>
                        Historique et traçabilité du dossier
                    </h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <!-- REOUVERTURES EVENTUELLES -->
                        @foreach ($missingForm->reopenings as $reopening)
                            <div class="timeline-event">
                                <div class="timeline-event-icon bg-transparent text-orange">
                                    <i class="ti ti-rotate fs-2"></i>
                                </div>
                                <div class="card timeline-event-card">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <div class="fw-bold text-orange">Dossier rouvert dans le Cycle #{{ $reopening->new_tracking_cycle }}</div>
                                            <div class="text-secondary small">{{ $reopening->reopened_at?->format('d/m/Y H:i') }}</div>
                                        </div>
                                        <div class="text-secondary small">Par : <span class="text-dark fw-medium">{{ $reopening->reopened_by_name }}</span></div>
                                        <div class="mt-1 text-dark small">Motif : {{ $reopening->reason }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- ETAPES DE FINALISATION -->
                        @foreach ($missingForm->finalizations as $finalization)
                            <div class="timeline-event">
                                <div class="timeline-event-icon bg-transparent text-success">
                                    <i class="ti ti-circle-check fs-2"></i>
                                </div>
                                <div class="card timeline-event-card">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <div class="fw-bold text-success">Étape validée : {{ $finalization->step->label() }}</div>
                                            <div class="text-secondary small">{{ $finalization->performed_at?->format('d/m/Y H:i') }}</div>
                                        </div>
                                        <div class="text-secondary small">Opérateur : <span class="text-dark fw-medium">{{ $finalization->performed_by_name }}</span></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- HISTORIQUE DES RECHERCHES -->
                        @foreach ($missingForm->searchHistory as $history)
                            @php
                                $historyResultValue = $history->mission_result?->value;
                                $historyColorClass = match ($historyResultValue) {
                                    'found' => 'text-success',
                                    'needs_verification' => 'text-orange',
                                    'not_found' => 'text-danger',
                                    default => 'text-primary',
                                };
                                $historyIconClass = match ($historyResultValue) {
                                    'found' => 'ti-circle-check',
                                    'needs_verification' => 'ti-alert-circle',
                                    'not_found' => 'ti-search-off',
                                    default => 'ti-search',
                                };
                            @endphp
                            <div class="timeline-event">
                                <div class="timeline-event-icon bg-transparent {{ $historyColorClass }}">
                                    <i class="ti {{ $historyIconClass }} fs-2"></i>
                                </div>
                                <div class="card timeline-event-card">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <div class="fw-bold {{ $historyColorClass }}">
                                                Recherche : {{ $history->mission_result?->label() ?? $history->form_status->label() }}
                                            </div>
                                            <div class="text-secondary small">{{ $history->searched_on?->format('d/m/Y') ?? $history->created_at?->format('d/m/Y H:i') }}</div>
                                        </div>
                                        <div class="text-secondary small">
                                            Responsable : <span class="text-dark fw-medium">{{ $history->responsible_name }}</span> · Localisation : <span class="text-dark fw-medium">{{ $history->search_location_name }}</span>
                                        </div>
                                        <div class="text-secondary small">Compte rendu saisi par : {{ $history->recorded_by_name }}</div>
                                        @if ($history->result_summary)
                                            <div class="mt-1 text-secondary small fst-italic">« {{ $history->result_summary }} »</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- DECLARATION DE DECOUVERTE DIRECTE -->
                        @if ($missingForm->directFoundReport)
                            @php
                                $directReport = $missingForm->directFoundReport;
                            @endphp
                            <div class="timeline-event">
                                <div class="timeline-event-icon bg-transparent text-success">
                                    <i class="ti ti-circle-check fs-2"></i>
                                </div>
                                <div class="card timeline-event-card">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <div class="fw-bold text-success">
                                                Enregistrement direct comme formulaire retrouvé
                                            </div>
                                            <div class="text-secondary small">{{ $directReport->found_on?->format('d/m/Y') ?? $directReport->created_at?->format('d/m/Y H:i') }}</div>
                                        </div>
                                        <div class="text-secondary small">
                                            Retrouvé par : <span class="text-dark fw-medium">{{ $directReport->finder_name }}</span> ({{ $directReport->finder_type->label() }})
                                            · Localisation : <span class="text-dark fw-medium">{{ $directReport->foundLocation?->location_name ?? $missingForm->lastSearchLocation?->location_name ?? '—' }}</span>
                                        </div>
                                        @if ($directReport->finder_organization || $directReport->finder_contact)
                                            <div class="text-secondary small">
                                                @if ($directReport->finder_organization)
                                                    Organisme : {{ $directReport->finder_organization }}
                                                @endif
                                                @if ($directReport->finder_organization && $directReport->finder_contact)
                                                    ·
                                                @endif
                                                @if ($directReport->finder_contact)
                                                    Contact : {{ $directReport->finder_contact }}
                                                @endif
                                            </div>
                                        @endif
                                        <div class="text-secondary small">Enregistré par : <span class="text-dark fw-medium">{{ $directReport->recorded_by_name }}</span></div>
                                        @if ($directReport->result_summary)
                                            <div class="mt-1 text-secondary small fst-italic">« {{ $directReport->result_summary }} »</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- CREATION INITIALE -->
                        @if (! $missingForm->directFoundReport)
                            <div class="timeline-event">
                                <div class="timeline-event-icon bg-transparent text-primary">
                                    <i class="ti ti-file-plus fs-2"></i>
                                </div>
                                <div class="card timeline-event-card">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <div class="fw-bold text-primary">Création du formulaire manquant</div>
                                            <div class="text-secondary small">{{ $missingForm->created_at?->format('d/m/Y H:i') }}</div>
                                        </div>
                                        <div class="text-secondary small">
                                            Enregistré par : <span class="text-dark fw-medium">{{ $missingForm->createdBy?->first_names }} {{ $missingForm->createdBy?->last_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('modals')
        <!-- MODALE AFFECTATION DE MISSION -->
        @can('missions_assign')
            <div class="modal modal-blur fade" id="modal-assign-mission" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <form class="modal-content" method="post" action="{{ route('missions.assign', $missingForm) }}" autocomplete="off" novalidate>
                        @csrf
                        <div class="modal-header">
                            <h3 class="modal-title">
                                <i class="ti ti-target me-2 text-primary"></i>
                                Affecter une mission de recherche
                            </h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required" for="responsible_user">Agent responsable</label>
                                <select class="form-select" id="responsible_user" name="responsible_user_code_unique" required>
                                    <option value="">Sélectionner un agent</option>
                                    @foreach ($responsibleUsers as $user)
                                        <option value="{{ $user->user_code_unique }}">
                                            {{ $user->first_names }} {{ $user->last_name }} ({{ $user->service?->service_name ?? 'OIPI' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required" for="search_location">Localisation physique de recherche</label>
                                <select class="form-select" id="search_location" name="search_location_code_unique" required>
                                    <option value="">Sélectionner une zone</option>
                                    @foreach ($searchLocations as $location)
                                        <option value="{{ $location->search_location_code_unique }}">
                                            {{ $location->location_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="due_date">Date d'échéance</label>
                                    <input class="form-control" id="due_date" name="due_date" type="date">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required" for="mission_priority">Priorité</label>
                                    <select class="form-select" id="mission_priority" name="priority" required>
                                        @foreach ($priorities as $priority)
                                            <option value="{{ $priority->value }}" @selected($priority->value === 'normal')>
                                                {{ $priority->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Affecter la mission</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        <!-- MODALE D'ARCHIVAGE DU DOSSIER -->
        @can('registry_archive')
            <div class="modal modal-blur fade" id="modal-archive-form" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <form class="modal-content" method="post" action="{{ route('registry.forms.archive', $missingForm) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-status bg-danger"></div>
                        <div class="modal-header">
                            <h3 class="modal-title">
                                <i class="ti ti-archive me-2 text-danger"></i>
                                Archiver le formulaire {{ $missingForm->registry_number }}
                            </h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-secondary small mb-3">
                                Veuillez indiquer la raison impérative justifiant l'archivage de ce dossier.
                            </p>
                            <div class="mb-3">
                                <label class="form-label required" for="archive_reason_modal">Motif de l'archivage</label>
                                <textarea
                                    class="form-control"
                                    id="archive_reason_modal"
                                    name="archive_reason"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Ex. Formulaire détruit lors du sinistre, radiation confirmée..."
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
        @endcan

        <!-- MODALE DE REOUVERTURE DU DOSSIER -->
        @can('registry_reopen')
            <div class="modal modal-blur fade" id="modal-reopen-form" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <form class="modal-content" method="post" action="{{ route('registry.forms.reopen', $missingForm) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-status bg-warning"></div>
                        <div class="modal-header">
                            <h3 class="modal-title">
                                <i class="ti ti-refresh me-2 text-warning"></i>
                                Rouvrir le formulaire {{ $missingForm->registry_number }}
                            </h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-secondary small mb-3">
                                La réouverture va incrémenter le cycle de recherche (passage au Cycle #{{ $missingForm->tracking_cycle + 1 }}), conserver l'ensemble des historiques antérieurs et replacer le formulaire en recherche.
                            </p>
                            <div class="mb-3">
                                <label class="form-label required" for="reopening_reason">Motif de la réouverture</label>
                                <textarea
                                    class="form-control"
                                    id="reopening_reason"
                                    name="reopening_reason"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Ex. Erreur matérielle sur le document initialement numérisé, nouvelle recherche requise..."
                                    required
                                ></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-warning">Confirmer la réouverture</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        <!-- MODALE D'AJOUT DE PIECE JOINTE -->
        @if ($canAddAttachments)
            <div class="modal modal-blur fade" id="modal-add-attachment" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <form
                        class="modal-content"
                        method="post"
                        action="{{ route('registry.forms.attachments.store', $missingForm) }}"
                        enctype="multipart/form-data"
                    >
                        @csrf
                        <div class="modal-header">
                            <h3 class="modal-title d-flex align-items-center gap-2">
                                <i class="ti ti-paperclip text-primary fs-3"></i>
                                <span>Ajouter une pièce jointe au dossier</span>
                            </h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required" for="attachment-input">Sélectionner un fichier</label>
                                <input
                                    type="file"
                                    class="form-control @error('attachment') is-invalid @enderror"
                                    id="attachment-input"
                                    name="attachment"
                                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                    required
                                >
                                @error('attachment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="p-3 rounded border bg-light small mb-0">
                                <div class="fw-semibold text-dark mb-1"><i class="ti ti-file-description text-primary me-1"></i>Exigences techniques et sécurité :</div>
                                <ul class="text-secondary mb-0 ps-3">
                                    <li>Formats acceptés : <strong>PDF, JPG, JPEG, PNG</strong></li>
                                    <li>Taille maximale autorisée : <strong>10 Mo</strong></li>
                                    <li>Stockage privé sécurisé hors de l'espace web public</li>
                                    <li>Contrôle d'intégrité SHA-256 et traçabilité dans le journal d'audit</li>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-upload me-1"></i>Téléverser la pièce jointe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- MODALES DE CONFIRMATION DE SUPPRESSION DE PIECE JOINTE -->
        @foreach ($missingForm->attachments as $attachment)
            <div class="modal modal-blur fade" id="modal-delete-attachment-{{ $attachment->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <form
                        class="modal-content"
                        method="post"
                        action="{{ route('registry.forms.attachments.destroy', [$missingForm, $attachment]) }}"
                    >
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h5 class="modal-title text-danger d-flex align-items-center gap-2">
                                <i class="ti ti-trash fs-3"></i>
                                <span>Supprimer la pièce jointe</span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-dark mb-2">
                                Êtes-vous certain de vouloir supprimer définitivement cette pièce jointe du dossier ?
                            </p>
                            <div class="bg-light p-3 rounded small mb-3">
                                <div><strong>Nom original :</strong> {{ $attachment->original_name }}</div>
                                <div><strong>Code pièce :</strong> <span class="font-monospace text-secondary">{{ $attachment->form_attachment_code_unique }}</span></div>
                                <div><strong>Taille :</strong> {{ number_format($attachment->size_bytes / 1024, 1, ',', ' ') }} Ko</div>
                            </div>
                            <div class="alert alert-warning small mb-0">
                                <i class="ti ti-alert-triangle me-1"></i>
                                Le fichier physique sera immédiatement supprimé du stockage sécurisé et archivé logiquement.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="ti ti-trash me-1"></i>Confirmer la suppression
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endpush
@endsection

