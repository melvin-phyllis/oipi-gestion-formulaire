@extends('layouts.app')

@section('title', 'Enregistrement au registre')

@section('content')
    @php
        $canRegisterFound = auth()->user()?->can('registry_register_found') ?? false;
        $hasFoundErrors = $errors->hasAny([
            'finder_type',
            'finder_user_code_unique',
            'finder_name',
            'finder_organization',
            'finder_contact',
            'found_location_code_unique',
            'found_on',
            'result_summary',
        ]);
        $isFoundMode = $canRegisterFound && (
            request('mode') === 'found'
            || old('registration_mode') === 'found'
            || old('finder_type') !== null
            || $hasFoundErrors
        );
        $selectedFinderType = old('finder_type', 'internal');
    @endphp

    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <h1 class="page-title" id="registry-create-page-title">
                    {{ $isFoundMode ? 'Enregistrer un formulaire déjà retrouvé' : 'Déclarer un formulaire manquant' }}
                </h1>
                <div class="text-secondary mt-1" id="registry-create-page-subtitle">
                    {{ $isFoundMode
                        ? 'Enregistrez directement un document physique retrouvé (statut initial Retrouvé, sans fausse mission de recherche).'
                        : 'Enregistrez un formulaire physique introuvable pour initier son cycle de recherche et de traçabilité.' }}
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex flex-wrap gap-2">
                @can('registry_import')
                    <button
                        type="button"
                        class="btn btn-outline-secondary d-inline-flex align-items-center"
                        data-bs-toggle="modal"
                        data-bs-target="#modal-import-missing-forms"
                    >
                        <i class="ti ti-file-import me-1" aria-hidden="true"></i>
                        Importer un lot
                    </button>
                @endcan
                <a class="btn btn-outline-secondary" href="{{ route('registry.forms.index') }}">
                    <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                    Retour au registre
                </a>
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

    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">
            @if ($canRegisterFound)
                <div class="card mb-3">
                    <div class="card-body p-2">
                        <div class="nav nav-pills nav-fill" role="tablist" aria-label="Mode d'enregistrement du formulaire">
                            <button
                                class="nav-link {{ ! $isFoundMode ? 'active' : '' }}"
                                id="tab-btn-missing"
                                data-bs-toggle="tab"
                                data-bs-target="#pane-register-missing"
                                type="button"
                                role="tab"
                                aria-controls="pane-register-missing"
                                aria-selected="{{ ! $isFoundMode ? 'true' : 'false' }}"
                            >
                                Formulaire manquant
                            </button>
                            <button
                                class="nav-link {{ $isFoundMode ? 'active' : '' }}"
                                id="tab-btn-found"
                                data-bs-toggle="tab"
                                data-bs-target="#pane-register-found"
                                type="button"
                                role="tab"
                                aria-controls="pane-register-found"
                                aria-selected="{{ $isFoundMode ? 'true' : 'false' }}"
                            >
                                Formulaire déjà retrouvé
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="tab-content">
                <!-- ============================================================== -->
                <!-- ONGLET 1 : DECLARER UN FORMULAIRE MANQUANT                      -->
                <!-- ============================================================== -->
                <div
                    class="tab-pane fade {{ ! $isFoundMode ? 'show active' : '' }}"
                    id="pane-register-missing"
                    role="tabpanel"
                    aria-labelledby="tab-btn-missing"
                >
                    <form method="post" action="{{ route('registry.forms.store') }}" autocomplete="off" novalidate>
                        @csrf
                        <input type="hidden" name="registration_mode" value="missing">

                        <div class="card">
                            <div class="card-header py-3">
                                <h3 class="card-title">
                                    <i class="ti ti-file-certificate me-2 text-primary" aria-hidden="true"></i>
                                    Informations d'identification du formulaire manquant
                                </h3>
                            </div>

                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- TYPE DE TITRE -->
                                    <div class="col-md-6">
                                        <label class="form-label required" for="title_type_code_unique">Type de titre de propriété</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon"><i class="ti ti-certificate text-secondary"></i></span>
                                            <select
                                                class="form-select @if(! $isFoundMode) @error('title_type_code_unique') is-invalid @enderror @endif"
                                                id="title_type_code_unique"
                                                name="title_type_code_unique"
                                                required
                                            >
                                                <option value="">Sélectionner un type de titre</option>
                                                @foreach ($titleTypes as $titleType)
                                                    <option value="{{ $titleType->title_type_code_unique }}" @selected(! $isFoundMode && old('title_type_code_unique') === $titleType->title_type_code_unique)>
                                                        {{ $titleType->title_type_code }} — {{ $titleType->title_type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @if (! $isFoundMode)
                                            @error('title_type_code_unique') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <!-- ANNEE -->
                                    <div class="col-md-3">
                                        <label class="form-label required" for="form_year">Année du formulaire</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon"><i class="ti ti-calendar text-secondary"></i></span>
                                            <input
                                                class="form-control @if(! $isFoundMode) @error('form_year') is-invalid @enderror @endif"
                                                id="form_year"
                                                name="form_year"
                                                type="number"
                                                min="1900"
                                                max="{{ date('Y') }}"
                                                placeholder="{{ date('Y') }}"
                                                value="{{ ! $isFoundMode ? old('form_year', date('Y')) : date('Y') }}"
                                                required
                                            >
                                        </div>
                                        @if (! $isFoundMode)
                                            @error('form_year') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <!-- PRIORITE -->
                                    <div class="col-md-3">
                                        <label class="form-label required" for="priority">Priorité initiale</label>
                                        <select
                                            class="form-select @if(! $isFoundMode) @error('priority') is-invalid @enderror @endif"
                                            id="priority"
                                            name="priority"
                                            required
                                        >
                                            @foreach ($priorities as $priority)
                                                <option value="{{ $priority->value }}" @selected((! $isFoundMode ? old('priority', 'normal') : 'normal') === $priority->value)>
                                                    {{ $priority->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (! $isFoundMode)
                                            @error('priority') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        @endif
                                    </div>

                                    <!-- NUMERO DU FORMULAIRE -->
                                    <div class="col-md-6">
                                        <label class="form-label required" for="form_number">Numéro du formulaire</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon"><i class="ti ti-hash text-secondary"></i></span>
                                            <input
                                                class="form-control @if(! $isFoundMode) @error('form_number') is-invalid @enderror @endif"
                                                id="form_number"
                                                name="form_number"
                                                type="text"
                                                maxlength="60"
                                                placeholder="ex. B-2023-000154"
                                                value="{{ ! $isFoundMode ? old('form_number') : '' }}"
                                                required
                                            >
                                        </div>
                                        @if (! $isFoundMode)
                                            @error('form_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        @endif
                                        <div class="form-hint small text-secondary mt-1">Le triplet Année + Type de titre + N° Formulaire doit être unique.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer d-flex justify-content-between align-items-center py-3">
                                <a class="btn btn-outline-secondary" href="{{ route('registry.forms.index') }}">
                                    Annuler
                                </a>
                                <button class="btn btn-primary" type="submit">
                                    <i class="ti ti-check me-1" aria-hidden="true"></i>
                                    Enregistrer le formulaire dans le registre
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ============================================================== -->
                <!-- ONGLET 2 : ENREGISTRER UN FORMULAIRE DEJA RETROUVE              -->
                <!-- ============================================================== -->
                @if ($canRegisterFound)
                    <div
                        class="tab-pane fade {{ $isFoundMode ? 'show active' : '' }}"
                        id="pane-register-found"
                        role="tabpanel"
                        aria-labelledby="tab-btn-found"
                    >
                        <form method="post" action="{{ route('registry.forms.store-found') }}" autocomplete="off" novalidate id="form-register-found">
                            @csrf
                            <input type="hidden" name="registration_mode" value="found">

                            <div class="card">
                                <div class="card-header py-3">
                                    <h3 class="card-title mb-0">
                                        <i class="ti ti-circle-check me-2 text-success" aria-hidden="true"></i>
                                        Enregistrement direct d'un formulaire déjà retrouvé
                                    </h3>
                                </div>

                                <div class="card-body">
                                    <!-- SECTION 1 : IDENTIFICATION DU FORMULAIRE -->
                                    <div class="hr-text text-primary mt-0 mb-3">1. Identification du formulaire</div>
                                    <div class="row g-3 mb-4">
                                        <!-- TYPE DE TITRE -->
                                        <div class="col-md-6">
                                            <label class="form-label required" for="found_title_type_code_unique">Type de titre de propriété</label>
                                            <div class="input-icon">
                                                <span class="input-icon-addon"><i class="ti ti-certificate text-secondary"></i></span>
                                                <select
                                                    class="form-select @if($isFoundMode) @error('title_type_code_unique') is-invalid @enderror @endif"
                                                    id="found_title_type_code_unique"
                                                    name="title_type_code_unique"
                                                    required
                                                >
                                                    <option value="">Sélectionner un type de titre</option>
                                                    @foreach ($titleTypes as $titleType)
                                                        <option value="{{ $titleType->title_type_code_unique }}" @selected($isFoundMode && old('title_type_code_unique') === $titleType->title_type_code_unique)>
                                                            {{ $titleType->title_type_code }} — {{ $titleType->title_type_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @if ($isFoundMode)
                                                @error('title_type_code_unique') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @endif
                                        </div>

                                        <!-- ANNEE -->
                                        <div class="col-md-3">
                                            <label class="form-label required" for="found_form_year">Année du formulaire</label>
                                            <div class="input-icon">
                                                <span class="input-icon-addon"><i class="ti ti-calendar text-secondary"></i></span>
                                                <input
                                                    class="form-control @if($isFoundMode) @error('form_year') is-invalid @enderror @endif"
                                                    id="found_form_year"
                                                    name="form_year"
                                                    type="number"
                                                    min="1900"
                                                    max="{{ date('Y') }}"
                                                    placeholder="{{ date('Y') }}"
                                                    value="{{ $isFoundMode ? old('form_year', date('Y')) : date('Y') }}"
                                                    required
                                                >
                                            </div>
                                            @if ($isFoundMode)
                                                @error('form_year') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @endif
                                        </div>

                                        <!-- PRIORITE -->
                                        <div class="col-md-3">
                                            <label class="form-label required" for="found_priority">Priorité de traitement</label>
                                            <select
                                                class="form-select @if($isFoundMode) @error('priority') is-invalid @enderror @endif"
                                                id="found_priority"
                                                name="priority"
                                                required
                                            >
                                                @foreach ($priorities as $priority)
                                                    <option value="{{ $priority->value }}" @selected(($isFoundMode ? old('priority', 'normal') : 'normal') === $priority->value)>
                                                        {{ $priority->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($isFoundMode)
                                                @error('priority') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @endif
                                        </div>

                                        <!-- NUMERO DU FORMULAIRE -->
                                        <div class="col-md-6">
                                            <label class="form-label required" for="found_form_number">Numéro du formulaire</label>
                                            <div class="input-icon">
                                                <span class="input-icon-addon"><i class="ti ti-hash text-secondary"></i></span>
                                                <input
                                                    class="form-control @if($isFoundMode) @error('form_number') is-invalid @enderror @endif"
                                                    id="found_form_number"
                                                    name="form_number"
                                                    type="text"
                                                    maxlength="60"
                                                    placeholder="ex. B-2023-000154"
                                                    value="{{ $isFoundMode ? old('form_number') : '' }}"
                                                    required
                                                >
                                            </div>
                                            @if ($isFoundMode)
                                                @error('form_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @endif
                                            <div class="form-hint small text-secondary mt-1">Le triplet Année + Type de titre + N° Formulaire doit être unique dans le registre.</div>
                                        </div>
                                    </div>

                                    <!-- SECTION 2 : PERSONNE AYANT RETROUVE LE DOCUMENT -->
                                    <div class="hr-text text-primary my-3">2. Personne ayant retrouvé le document</div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-12">
                                            <label class="form-label required mb-2">Profil de la personne ayant retrouvé le formulaire</label>
                                            <div class="row g-3 form-selectgroup-boxes">
                                                <div class="col-12 col-md-6">
                                                    <label class="form-selectgroup-item m-0 w-100 h-100">
                                                        <input
                                                            type="radio"
                                                            name="finder_type"
                                                            value="internal"
                                                            class="form-selectgroup-input js-finder-type-radio"
                                                            @checked($selectedFinderType === 'internal')
                                                        >
                                                        <div class="form-selectgroup-label d-flex align-items-center justify-content-between p-3 h-100">
                                                            <div class="d-flex align-items-center">
                                                                <i class="ti ti-user-check text-primary fs-1 me-3"></i>
                                                                <div>
                                                                    <div class="fw-bold text-dark">Utilisateur RISFM (Interne)</div>
                                                                    <div class="text-secondary small">Agent ou collaborateur actif disposant d'un compte RISFM.</div>
                                                                </div>
                                                            </div>
                                                            <div class="form-selectgroup-check ms-3 flex-shrink-0"></div>
                                                        </div>
                                                    </label>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <label class="form-selectgroup-item m-0 w-100 h-100">
                                                        <input
                                                            type="radio"
                                                            name="finder_type"
                                                            value="external"
                                                            class="form-selectgroup-input js-finder-type-radio"
                                                            @checked($selectedFinderType === 'external')
                                                        >
                                                        <div class="form-selectgroup-label d-flex align-items-center justify-content-between p-3 h-100">
                                                            <div class="d-flex align-items-center">
                                                                <i class="ti ti-building-community text-orange fs-1 me-3"></i>
                                                                <div>
                                                                    <div class="fw-bold text-dark">Personne externe</div>
                                                                    <div class="text-secondary small">Collaborateur hors RISFM, cabinet, déposant ou intervenant externe.</div>
                                                                </div>
                                                            </div>
                                                            <div class="form-selectgroup-check ms-3 flex-shrink-0"></div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                            @error('finder_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>

                                        <!-- CHAMPS SI INTERNE -->
                                        <div class="col-12 {{ $selectedFinderType === 'internal' ? '' : 'd-none' }}" id="finder-internal-fields">
                                            <div class="row g-3">
                                                <div class="col-md-8">
                                                    <label class="form-label required" for="finder_user_code_unique">Utilisateur actif RISFM</label>
                                                    <div class="input-icon">
                                                        <span class="input-icon-addon"><i class="ti ti-user text-secondary"></i></span>
                                                        <select
                                                            class="form-select @error('finder_user_code_unique') is-invalid @enderror"
                                                            id="finder_user_code_unique"
                                                            name="finder_user_code_unique"
                                                            @disabled($selectedFinderType !== 'internal')
                                                        >
                                                            <option value="">Sélectionner l'agent ou collaborateur</option>
                                                            @foreach ($finderUsers ?? [] as $user)
                                                                <option value="{{ $user->user_code_unique }}" @selected(old('finder_user_code_unique') === $user->user_code_unique)>
                                                                    {{ $user->last_name }} {{ $user->first_names }} ({{ $user->service?->service_name ?? 'OIPI' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @error('finder_user_code_unique') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                    <div class="form-hint small text-secondary mt-1">Le nom complet et le service seront automatiquement mémorisés.</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CHAMPS SI EXTERNE -->
                                        <div class="col-12 {{ $selectedFinderType === 'external' ? '' : 'd-none' }}" id="finder-external-fields">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label required" for="finder_name">Nom et prénoms (externe)</label>
                                                    <div class="input-icon">
                                                        <span class="input-icon-addon"><i class="ti ti-user-edit text-secondary"></i></span>
                                                        <input
                                                            type="text"
                                                            class="form-control @error('finder_name') is-invalid @enderror"
                                                            id="finder_name"
                                                            name="finder_name"
                                                            maxlength="220"
                                                            placeholder="ex. YAO Clarisse"
                                                            value="{{ old('finder_name') }}"
                                                            @disabled($selectedFinderType !== 'external')
                                                        >
                                                    </div>
                                                    @error('finder_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label" for="finder_organization">Organisme / Structure <span class="text-secondary fw-normal">(facultatif)</span></label>
                                                    <div class="input-icon">
                                                        <span class="input-icon-addon"><i class="ti ti-building text-secondary"></i></span>
                                                        <input
                                                            type="text"
                                                            class="form-control @error('finder_organization') is-invalid @enderror"
                                                            id="finder_organization"
                                                            name="finder_organization"
                                                            maxlength="255"
                                                            placeholder="ex. Cabinet Conseil PI"
                                                            value="{{ old('finder_organization') }}"
                                                            @disabled($selectedFinderType !== 'external')
                                                        >
                                                    </div>
                                                    @error('finder_organization') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label" for="finder_contact">Contact téléphonique / E-mail <span class="text-secondary fw-normal">(facultatif)</span></label>
                                                    <div class="input-icon">
                                                        <span class="input-icon-addon"><i class="ti ti-phone text-secondary"></i></span>
                                                        <input
                                                            type="text"
                                                            class="form-control @error('finder_contact') is-invalid @enderror"
                                                            id="finder_contact"
                                                            name="finder_contact"
                                                            maxlength="150"
                                                            placeholder="ex. +225 01 02 03 04 05"
                                                            value="{{ old('finder_contact') }}"
                                                            @disabled($selectedFinderType !== 'external')
                                                        >
                                                    </div>
                                                    @error('finder_contact') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SECTION 3 : CIRCONSTANCES DE LA DECOUVERTE -->
                                    <div class="hr-text text-primary my-3">3. Localisation, date et compte rendu de découverte</div>
                                    <div class="row g-3">
                                        <!-- LOCALISATION -->
                                        <div class="col-md-6">
                                            <label class="form-label required" for="found_location_code_unique">Localisation où le formulaire a été retrouvé</label>
                                            <div class="input-icon">
                                                <span class="input-icon-addon"><i class="ti ti-map-pin text-secondary"></i></span>
                                                <select
                                                    class="form-select @error('found_location_code_unique') is-invalid @enderror"
                                                    id="found_location_code_unique"
                                                    name="found_location_code_unique"
                                                    required
                                                >
                                                    <option value="">Sélectionner la localisation physique</option>
                                                    @foreach ($searchLocations ?? [] as $location)
                                                        <option value="{{ $location->search_location_code_unique }}" @selected(old('found_location_code_unique') === $location->search_location_code_unique)>
                                                            {{ $location->location_name }} ({{ $location->location_code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('found_location_code_unique') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>

                                        <!-- DATE DE DECOUVERTE -->
                                        <div class="col-md-6">
                                            <label class="form-label required" for="found_on">Date de la découverte</label>
                                            <div class="input-icon">
                                                <span class="input-icon-addon"><i class="ti ti-calendar-event text-secondary"></i></span>
                                                <input
                                                    type="date"
                                                    class="form-control @error('found_on') is-invalid @enderror"
                                                    id="found_on"
                                                    name="found_on"
                                                    max="{{ date('Y-m-d') }}"
                                                    value="{{ old('found_on', date('Y-m-d')) }}"
                                                    required
                                                >
                                            </div>
                                            @error('found_on') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            <div class="form-hint small text-secondary mt-1">La date de découverte ne peut pas être située dans le futur.</div>
                                        </div>

                                        <!-- COMPTE RENDU OBLIGATOIRE -->
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label required mb-0" for="found_result_summary">Compte rendu de découverte</label>
                                                <span class="text-secondary small font-monospace" id="found_summary_counter">0 / 2000</span>
                                            </div>
                                            <textarea
                                                class="form-control @error('result_summary') is-invalid @enderror"
                                                id="found_result_summary"
                                                name="result_summary"
                                                rows="3"
                                                maxlength="2000"
                                                placeholder="Précisez les circonstances de la découverte, l'état matériel du formulaire et son emplacement exact..."
                                                required
                                            >{{ old('result_summary') }}</textarea>
                                            @error('result_summary') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-between align-items-center py-3">
                                    <a class="btn btn-outline-secondary" href="{{ route('registry.forms.index') }}">
                                        Annuler
                                    </a>
                                    <button class="btn btn-success" type="submit">
                                        <i class="ti ti-circle-check me-1" aria-hidden="true"></i>
                                        Enregistrer comme formulaire retrouvé
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($canRegisterFound)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const titleEl = document.getElementById('registry-create-page-title');
                    const subtitleEl = document.getElementById('registry-create-page-subtitle');
                    const tabMissing = document.getElementById('tab-btn-missing');
                    const tabFound = document.getElementById('tab-btn-found');

                    if (tabMissing && tabFound && titleEl && subtitleEl) {
                        tabMissing.addEventListener('shown.bs.tab', function () {
                            titleEl.textContent = 'Déclarer un formulaire manquant';
                            subtitleEl.textContent = 'Enregistrez un formulaire physique introuvable pour initier son cycle de recherche et de traçabilité.';
                        });
                        tabFound.addEventListener('shown.bs.tab', function () {
                            titleEl.textContent = 'Enregistrer un formulaire déjà retrouvé';
                            subtitleEl.textContent = 'Enregistrez directement un document physique retrouvé (statut initial Retrouvé, sans fausse mission de recherche).';
                        });
                    }

                    const finderRadios = document.querySelectorAll('.js-finder-type-radio');
                    const internalBlock = document.getElementById('finder-internal-fields');
                    const externalBlock = document.getElementById('finder-external-fields');
                    const internalSelect = document.getElementById('finder_user_code_unique');
                    const externalInputs = externalBlock ? externalBlock.querySelectorAll('input') : [];

                    function syncFinderFields(type) {
                        const isInternal = type === 'internal';
                        if (internalBlock) {
                            internalBlock.classList.toggle('d-none', !isInternal);
                        }
                        if (externalBlock) {
                            externalBlock.classList.toggle('d-none', isInternal);
                        }
                        if (internalSelect) {
                            internalSelect.disabled = !isInternal;
                            internalSelect.required = isInternal;
                        }
                        externalInputs.forEach(function (input) {
                            input.disabled = isInternal;
                            if (input.id === 'finder_name') {
                                input.required = !isInternal;
                            }
                        });
                    }

                    finderRadios.forEach(function (radio) {
                        radio.addEventListener('change', function () {
                            if (this.checked) {
                                syncFinderFields(this.value);
                            }
                        });
                    });

                    const checkedRadio = document.querySelector('.js-finder-type-radio:checked');
                    if (checkedRadio) {
                        syncFinderFields(checkedRadio.value);
                    }

                    const summaryInput = document.getElementById('found_result_summary');
                    const summaryCounter = document.getElementById('found_summary_counter');
                    if (summaryInput && summaryCounter) {
                        const updateCounter = function () {
                            summaryCounter.textContent = summaryInput.value.length + ' / 2000';
                        };
                        summaryInput.addEventListener('input', updateCounter);
                        updateCounter();
                    }
                });
            </script>
        @endpush
    @endif

    @include('registry.forms._import_modal')
@endsection
