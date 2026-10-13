@extends('layouts.app')

@section('title', 'Mission pour ' . $mission->missingForm?->registry_number)

@section('content')
    <!-- EN-TETE DE PAGE -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Missions / Détail de mission</div>
                <h1 class="page-title d-flex flex-wrap align-items-center gap-2">
                    <span>Mission de recherche</span>
                    <a href="{{ route('registry.forms.show', $mission->missingForm) }}" class="badge bg-primary-lt text-primary fs-6 font-monospace text-decoration-none">
                        Dossier {{ $mission->missingForm?->registry_number }}
                    </a>
                    @if ($mission->status->value === 'assigned')
                        <span class="badge bg-yellow-lt fs-6">Affectée</span>
                    @elseif ($mission->status->value === 'in_progress')
                        <span class="badge bg-warning-lt text-warning fs-6">En cours</span>
                    @elseif ($mission->status->value === 'completed')
                        <span class="badge bg-green-lt fs-6">Terminée</span>
                    @elseif ($mission->status->value === 'cancelled')
                        <span class="badge bg-secondary-lt fs-6">Annulée</span>
                    @endif
                </h1>
                <div class="text-secondary mt-1">
                    Formulaire : <strong>{{ $mission->missingForm?->form_number }}</strong> ({{ $mission->missingForm?->titleType?->title_type_name }}) · Cycle de recherche #{{ $mission->tracking_cycle }}
                </div>
            </div>

            <!-- BOUTONS D'ACTION HAUT DE PAGE -->
            <div class="col-auto ms-auto d-print-none d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('missions.index') }}">
                    <i class="ti ti-arrow-left me-1"></i>
                    Retour aux missions
                </a>

                <!-- DEMARRER LA MISSION (AGENT OU RESPONSABLE) -->
                @if ($canRecordResult && $mission->status->value === 'assigned')
                    <form method="post" action="{{ route('missions.start', $mission) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-player-play me-1"></i>
                            Démarrer la recherche
                        </button>
                    </form>
                @endif

                <!-- SAISIR LE COMPTE RENDU (AGENT OU RESPONSABLE) -->
                @if ($canRecordResult && $mission->status->isActive())
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-record-result">
                        <i class="ti ti-clipboard-check me-1"></i>
                        Consigner le résultat
                    </button>
                @endif

                <!-- REAFFECTER LA MISSION -->
                @if ($canAssign && $mission->status->isActive())
                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modal-reassign-mission">
                        <i class="ti ti-refresh me-1"></i>
                        Réaffecter
                    </button>
                @endif

                <!-- ANNULER LA MISSION -->
                @if ($canAssign && $mission->status->isActive())
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-cancel-mission">
                        <i class="ti ti-x me-1"></i>
                        Annuler la mission
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- BANDEAU DE VALIDATION DU SIGNALEMENT RETROUVE -->
    @if ($canReviewFound)
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h3 class="card-title text-warning mb-1">
                            <i class="ti ti-alert-circle me-1"></i>
                            Signalement « Formulaire Retrouvé » en attente de votre validation
                        </h3>
                        <p class="text-secondary small mb-0">
                            L'agent a consigné avoir retrouvé ce document. En tant que responsable habilité, vous devez confirmer définitivement la découverte ou la refuser avec motif.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="post" action="{{ route('missions.found.confirm', $mission) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success">
                                <i class="ti ti-check me-1"></i>
                                Confirmer (Retrouvé)
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-reject-found">
                            <i class="ti ti-x me-1"></i>
                            Refuser le signalement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <!-- COLONNE GAUCHE : PARAMETRES DE LA MISSION -->
        <div class="col-12 col-xl-5">
            <div class="card mb-3">
                <div class="card-header py-3">
                    <h3 class="card-title">
                        <i class="ti ti-info-circle me-2 text-primary"></i>
                        Fiche d'affectation
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-secondary">Agent en charge</dt>
                        <dd class="col-7 fw-medium text-dark">
                            {{ $mission->responsible?->first_names }} {{ $mission->responsible?->last_name }}
                            <div class="small text-secondary">{{ $mission->responsible?->email }}</div>
                        </dd>

                        <dt class="col-5 text-secondary">Lieu / Salle</dt>
                        <dd class="col-7">
                            <span class="badge bg-light text-secondary border">
                                <i class="ti ti-map-pin me-1"></i>
                                {{ $mission->searchLocation?->location_name ?? '—' }}
                            </span>
                        </dd>

                        <dt class="col-5 text-secondary">Priorité</dt>
                        <dd class="col-7">
                            <span class="badge bg-secondary-lt">{{ $mission->priority->label() }}</span>
                        </dd>

                        <dt class="col-5 text-secondary">Échéance</dt>
                        <dd class="col-7">
                            @if ($mission->due_date)
                                <span class="{{ $mission->due_date->isPast() && $mission->status->isActive() ? 'text-danger fw-bold' : '' }}">
                                    {{ $mission->due_date->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-secondary">Non définie</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-secondary">Affecté par</dt>
                        <dd class="col-7 text-secondary">
                            {{ $mission->assignedBy?->first_names }} {{ $mission->assignedBy?->last_name }}
                            @if ($mission->assigned_at)
                                <div class="small">le {{ $mission->assigned_at->format('d/m/Y H:i') }}</div>
                            @endif
                        </dd>

                        <dt class="col-5 text-secondary">Date de recherche</dt>
                        <dd class="col-7">
                            {{ $mission->searched_on ? $mission->searched_on->format('d/m/Y') : 'Non renseignée' }}
                        </dd>

                        @if ($mission->closed_at)
                            <dt class="col-5 text-secondary">Clôturé le</dt>
                            <dd class="col-7">
                                {{ $mission->closed_at->format('d/m/Y H:i') }}
                                @if ($mission->closedBy)
                                    <div class="small text-secondary">par {{ $mission->closedBy->first_names }} {{ $mission->closedBy->last_name }}</div>
                                @endif
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- COMPTE RENDU ENREGISTRÉ -->
            @if ($mission->result || $mission->result_summary)
                <div class="card mb-3">
                    <div class="card-header py-3">
                        <h3 class="card-title">
                            <i class="ti ti-report me-2 text-primary"></i>
                            Compte rendu consigné
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <span class="text-secondary small">Résultat déclaré :</span>
                            @if ($mission->result?->value === 'found')
                                <span class="badge bg-success text-white ms-1">Retrouvé</span>
                            @elseif ($mission->result?->value === 'needs_verification')
                                <span class="badge bg-warning text-dark ms-1">À vérifier</span>
                            @elseif ($mission->result?->value === 'not_found')
                                <span class="badge bg-danger text-white ms-1">Non retrouvé</span>
                            @endif
                        </div>
                        @if ($mission->result_summary)
                            <div class="bg-surface p-3 rounded text-dark">
                                {{ $mission->result_summary }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- MOTIF D'ANNULATION -->
            @if ($mission->cancellation_reason)
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h3 class="card-title fs-5 text-danger"><i class="ti ti-x me-1"></i>Motif d'annulation</h3>
                    </div>
                    <div class="card-body py-2 text-danger">
                        {{ $mission->cancellation_reason }}
                    </div>
                </div>
            @endif
        </div>

        <!-- COLONNE DROITE : DOSSIER LIE ET DECISIONS DE VALIDATION -->
        <div class="col-12 col-xl-7">
            <!-- RECAPITULATIF DU DOSSIER CIBLE -->
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="ti ti-folder me-2 text-primary"></i>
                        Dossier cible du registre
                    </h3>
                    <a href="{{ route('registry.forms.show', $mission->missingForm) }}" class="btn btn-sm btn-outline-primary">
                        Voir le dossier complet
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="text-secondary small">Numéro de registre :</div>
                            <div class="fw-bold font-monospace fs-4 text-primary">{{ $mission->missingForm?->registry_number }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">Numéro du formulaire :</div>
                            <div class="fw-bold fs-4">{{ $mission->missingForm?->form_number }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">Type de titre :</div>
                            <div>{{ $mission->missingForm?->titleType?->title_type_name }} ({{ $mission->missingForm?->titleType?->title_type_code }})</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">Année du formulaire :</div>
                            <div>{{ $mission->missingForm?->form_year }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DECISION DE VALIDATION DU FORMULAIRE RETROUVE -->
            @if ($mission->foundReview)
                <div class="card mb-3">
                    <div class="card-header py-3">
                        <h3 class="card-title">
                            <i class="ti ti-gavel me-2 text-primary"></i>
                            Décision du responsable sur le signalement « Retrouvé »
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="text-secondary small">Statut de la revue :</span>
                            @if ($mission->foundReview->decision->value === 'confirmed')
                                <span class="badge bg-success text-white">Validé et Confirmé</span>
                            @else
                                <span class="badge bg-danger text-white">Refusé</span>
                            @endif
                        </div>
                        <div class="text-secondary small">
                            Par : <strong>{{ $mission->foundReview->reviewedBy?->first_names }} {{ $mission->foundReview->reviewedBy?->last_name }}</strong>
                            le {{ $mission->foundReview->reviewed_at?->format('d/m/Y H:i') }}
                        </div>
                        @if ($mission->foundReview->rejection_reason)
                            <div class="mt-2 p-2 bg-red-lt text-danger rounded small">
                                Motif du refus : {{ $mission->foundReview->rejection_reason }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- RAPPELS & RELANCES AUTOMATIQUES -->
            @if ($mission->reminders && $mission->reminders->count() > 0)
                <div class="card">
                    <div class="card-header py-3">
                        <h3 class="card-title">
                            <i class="ti ti-bell me-2 text-primary"></i>
                            Journal des relances automatiques ({{ $mission->reminders->count() }})
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type de rappel</th>
                                    <th>Destinataire</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mission->reminders as $reminder)
                                    <tr>
                                        <td class="text-secondary">{{ $reminder->database_sent_at?->format('d/m/Y H:i') ?? $reminder->created_at?->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-secondary-lt">{{ $reminder->type->label() }}</span>
                                        </td>
                                        <td>{{ $reminder->recipient?->first_names }} {{ $reminder->recipient?->last_name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('modals')
        <!-- MODALE CONSIGNER LE RESULTAT -->
        @if ($canRecordResult && $mission->status->isActive())
            <div class="modal modal-blur fade" id="modal-record-result" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <form class="modal-content" method="post" action="{{ route('missions.result.store', $mission) }}" autocomplete="off">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header py-3">
                            <div>
                                <h3 class="modal-title d-flex align-items-center gap-2">
                                    <i class="ti ti-clipboard-check text-success fs-2"></i>
                                    <span>Consigner le compte rendu de recherche</span>
                                </h3>
                                <div class="text-secondary small mt-1">
                                    Dossier <span class="font-monospace fw-bold text-dark">{{ $mission->missingForm?->registry_number }}</span>
                                    · Formulaire n° <strong>{{ $mission->missingForm?->form_number }}</strong>
                                    · Lieu : <span class="badge bg-light text-secondary border">{{ $mission->searchLocation?->location_name ?? 'Non assigné' }}</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>

                        <div class="modal-body py-3">
                            <!-- RESULTAT DE LA RECHERCHE PHYSIQUE -->
                            <div class="mb-3">
                                <label class="form-label required fw-bold mb-2">Résultat de la recherche physique</label>
                                <div class="row g-2 form-selectgroup-boxes" id="record-result-selectgroup">
                                    <!-- RETROUVE -->
                                    <div class="col-12 col-md-4">
                                        <label class="form-selectgroup-item m-0 w-100 h-100">
                                            <input
                                                type="radio"
                                                name="result"
                                                value="found"
                                                class="form-selectgroup-input record-result-radio"
                                                @checked(old('result') === 'found' || (!old('result') && $mission->result?->value === 'found') || (!old('result') && !$mission->result))
                                                required
                                            >
                                            <div class="form-selectgroup-label d-flex align-items-center justify-content-between p-3 h-100">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-circle-check text-green fs-1 me-2 flex-shrink-0"></i>
                                                    <div>
                                                        <div class="fw-bold text-dark">Retrouvé</div>
                                                        <div class="text-secondary small">Le document original a été localisé dans cette zone.</div>
                                                    </div>
                                                </div>
                                                <div class="form-selectgroup-check ms-2 flex-shrink-0"></div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- A VERIFIER -->
                                    <div class="col-12 col-md-4">
                                        <label class="form-selectgroup-item m-0 w-100 h-100">
                                            <input
                                                type="radio"
                                                name="result"
                                                value="needs_verification"
                                                class="form-selectgroup-input record-result-radio"
                                                @checked(old('result') === 'needs_verification' || (!old('result') && $mission->result?->value === 'needs_verification'))
                                            >
                                            <div class="form-selectgroup-label d-flex align-items-center justify-content-between p-3 h-100">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-help text-orange fs-1 me-2 flex-shrink-0"></i>
                                                    <div>
                                                        <div class="fw-bold text-dark">À vérifier</div>
                                                        <div class="text-secondary small">Document similaire, partiel ou doute sur le titulaire / l'armoire.</div>
                                                    </div>
                                                </div>
                                                <div class="form-selectgroup-check ms-2 flex-shrink-0"></div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- NON RETROUVE -->
                                    <div class="col-12 col-md-4">
                                        <label class="form-selectgroup-item m-0 w-100 h-100">
                                            <input
                                                type="radio"
                                                name="result"
                                                value="not_found"
                                                class="form-selectgroup-input record-result-radio"
                                                @checked(old('result') === 'not_found' || (!old('result') && $mission->result?->value === 'not_found'))
                                            >
                                            <div class="form-selectgroup-label d-flex align-items-center justify-content-between p-3 h-100">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-file-x text-red fs-1 me-2 flex-shrink-0"></i>
                                                    <div>
                                                        <div class="fw-bold text-dark">Non retrouvé</div>
                                                        <div class="text-secondary small">Recherche infructueuse après fouille .</div>
                                                    </div>
                                                </div>
                                                <div class="form-selectgroup-check ms-2 flex-shrink-0"></div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                @error('result')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <!-- ENCART D'INFORMATION DYNAMIQUE SELON LE CHOIX -->
                                <div id="record-result-notice" class="alert alert-success d-flex align-items-start gap-2 py-2 px-3 mt-2 mb-0">
                                    <i class="ti ti-info-circle fs-2 mt-1" id="record-notice-icon"></i>
                                    <div class="small" id="record-notice-text">
                                        La découverte physique sera consignée et soumise à la validation définitive du responsable du registre.
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- DATE DE RECHERCHE -->
                                <div class="col-12 col-md-5">
                                    <label class="form-label required" for="searched_on">Date de la recherche physique</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon"><i class="ti ti-calendar text-secondary"></i></span>
                                        <input
                                            class="form-control @error('searched_on') is-invalid @enderror"
                                            id="searched_on"
                                            name="searched_on"
                                            type="date"
                                            value="{{ old('searched_on', $mission->searched_on?->format('Y-m-d') ?? date('Y-m-d')) }}"
                                            max="{{ date('Y-m-d') }}"
                                            required
                                        >
                                    </div>
                                    @error('searched_on')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-hint small text-secondary">La date ne peut pas être située dans le futur.</div>
                                </div>

                                <!-- COMPTE RENDU DETAILLE -->
                                <div class="col-12 col-md-7">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label required mb-0" for="result_summary">Compte rendu détaillé / Observations</label>
                                        <span class="text-secondary small font-monospace" id="result_summary_counter">0 / 255</span>
                                    </div>
                                    <textarea
                                        class="form-control @error('result_summary') is-invalid @enderror"
                                        id="result_summary"
                                        name="result_summary"
                                        rows="3"
                                        minlength="3"
                                        maxlength="255"
                                        required
                                        placeholder="Ex. Dossier localisé dans l'armoire A3, chemise en bon état avec toutes les pièces..."
                                    >{{ old('result_summary', $mission->result_summary) }}</textarea>
                                    @error('result_summary')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-hint small text-secondary">Minimum 3 caractères, maximum 255 caractères.</div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer d-flex justify-content-between align-items-center py-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="ti ti-x me-1"></i>Annuler
                            </button>
                            <button type="submit" class="btn btn-success" id="btn-submit-result">
                                <i class="ti ti-check me-1"></i>Enregistrer le résultat
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- MODALE REFUS DU SIGNALEMENT RETROUVE -->
        @if ($canReviewFound)
            <div class="modal modal-blur fade" id="modal-reject-found" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <form class="modal-content" method="post" action="{{ route('missions.found.reject', $mission) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header py-3">
                            <h3 class="modal-title d-flex align-items-center gap-2">
                                <i class="ti ti-alert-triangle text-danger fs-2"></i>
                                <span>Refuser le signalement « Retrouvé »</span>
                            </h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body py-3">
                            <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                                <i class="ti ti-info-circle fs-2 mt-1"></i>
                                <div class="small">
                                    Vous vous apprêtez à invalider la découverte signalée par l'agent. Le dossier retournera à l'état de recherche active ou introuvable.
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label required mb-0" for="rejection_reason">Motif impératif du refus</label>
                                    <span class="text-secondary small font-monospace" id="rejection_reason_counter">0 / 500</span>
                                </div>
                                <textarea
                                    class="form-control @error('rejection_reason') is-invalid @enderror"
                                    id="rejection_reason"
                                    name="rejection_reason"
                                    rows="3"
                                    minlength="5"
                                    maxlength="500"
                                    placeholder="Précisez pourquoi la découverte n'est pas validée (ex. pièce d'identité manquante, mauvaise année)..."
                                    required
                                >{{ old('rejection_reason') }}</textarea>
                                @error('rejection_reason')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint small text-secondary">Minimum 5 caractères, maximum 500 caractères.</div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex justify-content-between align-items-center py-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="ti ti-x me-1"></i>Annuler
                            </button>
                            <button type="submit" class="btn btn-danger">
                                <i class="ti ti-ban me-1"></i>Confirmer le refus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- MODALE DE REAFFECTATION DE LA MISSION -->
        @if ($canAssign && $mission->status->isActive())
            <div class="modal modal-blur fade" id="modal-reassign-mission" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <form class="modal-content" method="post" action="{{ route('missions.reassign', $mission) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header py-3">
                            <div>
                                <h3 class="modal-title d-flex align-items-center gap-2">
                                    <i class="ti ti-refresh text-warning fs-2"></i>
                                    <span>Réaffecter la mission de recherche</span>
                                </h3>
                                <div class="text-secondary small mt-1">
                                    Dossier <span class="font-monospace fw-bold text-dark">{{ $mission->missingForm?->registry_number }}</span>
                                    · Cycle actuel #{{ $mission->tracking_cycle }}
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body py-3">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label required" for="reassign_responsible">Nouvel agent responsable</label>
                                    <select class="form-select @error('responsible_user_code_unique') is-invalid @enderror" id="reassign_responsible" name="responsible_user_code_unique" required>
                                        <option value="">Sélectionner un agent</option>
                                        @foreach ($responsibleUsers as $responsibleUser)
                                            <option value="{{ $responsibleUser->user_code_unique }}" @selected(old('responsible_user_code_unique', $mission->responsible_user_code_unique) === $responsibleUser->user_code_unique)>
                                                {{ $responsibleUser->first_names }} {{ $responsibleUser->last_name }}
                                                @if ($responsibleUser->service)
                                                    — {{ $responsibleUser->service->service_name }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('responsible_user_code_unique')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label required" for="reassign_location">Nouvelle localisation</label>
                                    <select class="form-select @error('search_location_code_unique') is-invalid @enderror" id="reassign_location" name="search_location_code_unique" required>
                                        <option value="">Sélectionner une localisation</option>
                                        @foreach ($searchLocations as $searchLocation)
                                            <option value="{{ $searchLocation->search_location_code_unique }}" @selected(old('search_location_code_unique', $mission->search_location_code_unique) === $searchLocation->search_location_code_unique)>
                                                {{ $searchLocation->location_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('search_location_code_unique')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="reassign_due_date">Nouvelle échéance</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon"><i class="ti ti-calendar text-secondary"></i></span>
                                        <input
                                            class="form-control @error('due_date') is-invalid @enderror"
                                            id="reassign_due_date"
                                            name="due_date"
                                            type="date"
                                            value="{{ old('due_date', $mission->due_date?->format('Y-m-d')) }}"
                                            min="{{ date('Y-m-d') }}"
                                        >
                                    </div>
                                    @error('due_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-hint small text-secondary">Optionnel · Doit être égale ou postérieure à aujourd'hui.</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label required" for="reassign_priority">Niveau de priorité</label>
                                    <select class="form-select @error('priority') is-invalid @enderror" id="reassign_priority" name="priority" required>
                                        @foreach ($priorities as $priority)
                                            <option value="{{ $priority->value }}" @selected(old('priority', $mission->priority->value) === $priority->value)>
                                                {{ $priority->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label required mb-0" for="reassignment_reason">Motif de la réaffectation</label>
                                        <span class="text-secondary small font-monospace" id="reassignment_reason_counter">0 / 500</span>
                                    </div>
                                    <textarea
                                        class="form-control @error('reassignment_reason') is-invalid @enderror"
                                        id="reassignment_reason"
                                        name="reassignment_reason"
                                        rows="2"
                                        minlength="5"
                                        maxlength="500"
                                        placeholder="Pourquoi réaffecter cette mission à cet agent ou lieu ?"
                                        required
                                    >{{ old('reassignment_reason') }}</textarea>
                                    @error('reassignment_reason')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-hint small text-secondary">Minimum 5 caractères, maximum 500 caractères.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex justify-content-between align-items-center py-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="ti ti-x me-1"></i>Annuler
                            </button>
                            <button type="submit" class="btn btn-warning">
                                <i class="ti ti-check me-1"></i>Confirmer la réaffectation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- MODALE D'ANNULATION DE LA MISSION -->
        @if ($canAssign && $mission->status->isActive())
            <div class="modal modal-blur fade" id="modal-cancel-mission" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <form class="modal-content" method="post" action="{{ route('missions.cancel', $mission) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header py-3">
                            <h3 class="modal-title d-flex align-items-center gap-2">
                                <i class="ti ti-x text-danger fs-2"></i>
                                <span>Annuler la mission de recherche</span>
                            </h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body py-3">
                            <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                                <i class="ti ti-alert-triangle fs-2 mt-1"></i>
                                <div class="small">
                                    L'annulation met fin immédiatement au cycle de recherche en cours. Cette action est irréversible et sera consignée dans l'audit.
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label required mb-0" for="cancellation_reason">Motif de l'annulation</label>
                                    <span class="text-secondary small font-monospace" id="cancellation_reason_counter">0 / 500</span>
                                </div>
                                <textarea
                                    class="form-control @error('cancellation_reason') is-invalid @enderror"
                                    id="cancellation_reason"
                                    name="cancellation_reason"
                                    rows="3"
                                    minlength="5"
                                    maxlength="500"
                                    placeholder="Indiquez impérativement la raison de l'annulation de cette tâche..."
                                    required
                                >{{ old('cancellation_reason') }}</textarea>
                                @error('cancellation_reason')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint small text-secondary">Minimum 5 caractères, maximum 500 caractères.</div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex justify-content-between align-items-center py-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="ti ti-x me-1"></i>Annuler
                            </button>
                            <button type="submit" class="btn btn-danger">
                                <i class="ti ti-trash me-1"></i>Confirmer l'annulation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Compteurs dynamiques de caractères
                const countFields = [
                    { id: 'result_summary', counterId: 'result_summary_counter', max: 255 },
                    { id: 'reassignment_reason', counterId: 'reassignment_reason_counter', max: 500 },
                    { id: 'cancellation_reason', counterId: 'cancellation_reason_counter', max: 500 },
                    { id: 'rejection_reason', counterId: 'rejection_reason_counter', max: 500 },
                ];

                countFields.forEach(field => {
                    const input = document.getElementById(field.id);
                    const counter = document.getElementById(field.counterId);
                    if (input && counter) {
                        const update = () => {
                            const length = (input.value || '').length;
                            counter.textContent = `${length} / ${field.max}`;
                            if (length >= field.max) {
                                counter.classList.add('text-danger', 'fw-bold');
                            } else {
                                counter.classList.remove('text-danger', 'fw-bold');
                            }
                        };
                        input.addEventListener('input', update);
                        update();
                    }
                });

                // Dynamique du formulaire de résultat de recherche
                const resultRadios = document.querySelectorAll('.record-result-radio');
                const noticeBox = document.getElementById('record-result-notice');
                const noticeIcon = document.getElementById('record-notice-icon');
                const noticeText = document.getElementById('record-notice-text');
                const submitBtn = document.getElementById('btn-submit-result');

                const notices = {
                    found: {
                        alertClass: 'alert-success',
                        iconClass: 'ti ti-circle-check',
                        text: 'La découverte physique sera consignée et soumise à la validation définitive du responsable du registre.',
                        btnClass: 'btn-success',
                        btnText: 'Enregistrer le résultat (Retrouvé)'
                    },
                    needs_verification: {
                        alertClass: 'alert-warning',
                        iconClass: 'ti ti-help',
                        text: 'Document partiel ou concordance incertaine : précisez impérativement vos doutes dans le compte rendu.',
                        btnClass: 'btn-warning',
                        btnText: 'Enregistrer le résultat (À vérifier)'
                    },
                    not_found: {
                        alertClass: 'alert-danger',
                        iconClass: 'ti ti-file-x',
                        text: 'Aucun document physique retrouvé dans cette zone : la mission sera clôturée comme non retrouvée pour ce lieu.',
                        btnClass: 'btn-danger',
                        btnText: 'Enregistrer le résultat (Non retrouvé)'
                    }
                };

                function updateResultFeedback() {
                    const checkedRadio = document.querySelector('.record-result-radio:checked');
                    if (!checkedRadio || !notices[checkedRadio.value]) return;

                    const config = notices[checkedRadio.value];

                    if (noticeBox) {
                        noticeBox.className = `alert ${config.alertClass} d-flex align-items-start gap-2 py-2 px-3 mt-2 mb-0`;
                    }

                    if (noticeIcon) {
                        noticeIcon.className = `${config.iconClass} fs-2 mt-1`;
                    }

                    if (noticeText) {
                        noticeText.textContent = config.text;
                    }

                    if (submitBtn) {
                        submitBtn.className = `btn ${config.btnClass}`;
                        submitBtn.innerHTML = `<i class="ti ti-check me-1"></i>${config.btnText}`;
                    }
                }

                resultRadios.forEach(radio => {
                    radio.addEventListener('change', updateResultFeedback);
                });

                updateResultFeedback();
            });
        </script>
    @endpush
@endsection

