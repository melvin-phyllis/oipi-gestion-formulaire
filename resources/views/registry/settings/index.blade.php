@extends('layouts.app')

@section('title', 'Listes métier du registre')

@php
    $activeTab = session('active_registry_tab', 'title-types');
@endphp

@section('content')
    <!-- PAGE HEADER TABLER -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Paramètres / Référentiels</div>
                <h1 class="page-title">Listes métier du registre</h1>
                <div class="text-secondary mt-1">Configurez les types de titres de propriété intellectuelle et les localisations physiques de recherche.</div>
            </div>
        </div>
    </div>

    <!-- BANDEAU INFO STATUTS DU WORKFLOW -->
    <div class="p-3 rounded border bg-light d-flex align-items-start mb-4" role="status">
        <i class="ti ti-lock fs-1 text-primary me-3 mt-1" aria-hidden="true"></i>
        <div class="flex-fill">
            <h3 class="mb-1 text-dark fw-bold">Les statuts du workflow sont verrouillés</h3>
            <div class="text-secondary small mb-2">
                Conformément aux règles de sécurité et à l'architecture du projet RISFM, les statuts et transitions du registre ne sont pas modifiables en base afin de préserver l'intégrité de la traçabilité.
            </div>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($statuses as $status)
                    <span class="badge bg-white text-dark border shadow-none">
                        <span class="badge badge-dot bg-{{ $status->color() }} me-1"></span>
                        {{ $status->label() }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ONGLETS DE PARAMÉTRAGE TABLER -->
    <div class="card">
        <div class="card-header border-bottom-0 pb-0">
            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'title-types' ? 'active' : '' }}" data-bs-toggle="tab" href="#title-types" role="tab" aria-selected="{{ $activeTab === 'title-types' ? 'true' : 'false' }}">
                        <i class="ti ti-certificate me-2 text-primary" aria-hidden="true"></i>
                        Types de titres
                        <span class="badge bg-primary-lt ms-2">{{ $titleTypes->count() }}</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'search-locations' ? 'active' : '' }}" data-bs-toggle="tab" href="#search-locations" role="tab" aria-selected="{{ $activeTab === 'search-locations' ? 'true' : 'false' }}">
                        <i class="ti ti-map-pin me-2 text-primary" aria-hidden="true"></i>
                        Localisations de recherche
                        <span class="badge bg-primary-lt ms-2">{{ $searchLocations->count() }}</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            <!-- ONGLET 1 : TYPES DE TITRES -->
            <div class="tab-pane {{ $activeTab === 'title-types' ? 'active show' : '' }}" id="title-types" role="tabpanel">
                <div class="card-body border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div class="text-secondary small">
                        Catégories officielles de titres (brevets, marques, dessins, modèles...).
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-title-type" type="button">
                        <i class="ti ti-plus me-1" aria-hidden="true"></i>
                        Ajouter un type de titre
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th class="w-1">Ordre</th>
                                <th>Sigle / Code</th>
                                <th>Intitulé du type de titre</th>
                                <th>Statut</th>
                                <th class="w-1 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($titleTypes as $titleType)
                                <tr class="{{ $titleType->trashed() ? 'opacity-75' : '' }}">
                                    <td class="text-secondary font-monospace">#{{ $titleType->display_order }}</td>
                                    <td>
                                        <span class="badge bg-primary-lt text-primary fw-bold fs-6">{{ $titleType->title_type_code }}</span>
                                    </td>
                                    <td class="fw-semibold text-dark">{{ $titleType->title_type_name }}</td>
                                    <td>
                                        @if ($titleType->trashed())
                                            <span class="status status-danger">
                                                <span class="status-dot"></span>
                                                Supprimé
                                            </span>
                                        @elseif ($titleType->is_active)
                                            <span class="status status-success">
                                                <span class="status-dot"></span>
                                                Actif
                                            </span>
                                        @else
                                            <span class="status status-secondary">
                                                <span class="status-dot"></span>
                                                Inactif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-action dropdown-toggle align-text-top" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                @if ($titleType->trashed())
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modal-title-type-restore-{{ $titleType->uuid }}"
                                                    >
                                                        <i class="ti ti-restore me-2 dropdown-item-icon text-success" aria-hidden="true"></i>
                                                        Restaurer
                                                    </button>
                                                @else
                                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-title-type-{{ $titleType->uuid }}" type="button">
                                                        <i class="ti ti-edit me-2 dropdown-item-icon text-primary" aria-hidden="true"></i>
                                                        Modifier
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item {{ $titleType->is_active ? 'text-danger' : 'text-success' }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modal-title-type-status-{{ $titleType->uuid }}"
                                                    >
                                                        <i class="ti {{ $titleType->is_active ? 'ti-power text-danger' : 'ti-power text-success' }} me-2 dropdown-item-icon" aria-hidden="true"></i>
                                                        {{ $titleType->is_active ? 'Désactiver' : 'Activer' }}
                                                    </button>
                                                    <div class="dropdown-divider"></div>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modal-title-type-delete-{{ $titleType->uuid }}"
                                                    >
                                                        <i class="ti ti-trash me-2 dropdown-item-icon text-danger" aria-hidden="true"></i>
                                                        Supprimer
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-secondary py-5" colspan="5">
                                        Aucun type de titre configuré.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ONGLET 2 : LOCALISATIONS DE RECHERCHE -->
            <div class="tab-pane {{ $activeTab === 'search-locations' ? 'active show' : '' }}" id="search-locations" role="tabpanel">
                <div class="card-body border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div class="text-secondary small">
                        Lieux d'archivage et de stockage physique pour l'attribution des missions de recherche.
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-search-location" type="button">
                        <i class="ti ti-plus me-1" aria-hidden="true"></i>
                        Ajouter une localisation
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th class="w-1">Ordre</th>
                                <th>Lieu / Salle</th>
                                <th>Description / Consignes</th>
                                <th>Code technique</th>
                                <th>Statut</th>
                                <th class="w-1 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($searchLocations as $searchLocation)
                                <tr>
                                    <td class="text-secondary font-monospace">#{{ $searchLocation->display_order }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $searchLocation->location_name }}</div>
                                    </td>
                                    <td class="text-secondary small">
                                        {{ $searchLocation->description ?: '—' }}
                                    </td>
                                    <td>
                                        <code class="text-muted">{{ $searchLocation->location_code }}</code>
                                    </td>
                                    <td>
                                        @if ($searchLocation->trashed())
                                            <span class="status status-danger">
                                                <span class="status-dot"></span>
                                                Supprimée
                                            </span>
                                        @elseif ($searchLocation->is_active)
                                            <span class="status status-success">
                                                <span class="status-dot"></span>
                                                Actif
                                            </span>
                                        @else
                                            <span class="status status-secondary">
                                                <span class="status-dot"></span>
                                                Inactif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-action dropdown-toggle align-text-top" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                @if ($searchLocation->trashed())
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modal-search-location-restore-{{ $searchLocation->uuid }}"
                                                    >
                                                        <i class="ti ti-restore me-2 dropdown-item-icon text-success" aria-hidden="true"></i>
                                                        Restaurer
                                                    </button>
                                                @else
                                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-search-location-{{ $searchLocation->uuid }}" type="button">
                                                        <i class="ti ti-edit me-2 dropdown-item-icon text-primary" aria-hidden="true"></i>
                                                        Modifier
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item {{ $searchLocation->is_active ? 'text-danger' : 'text-success' }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modal-search-location-status-{{ $searchLocation->uuid }}"
                                                    >
                                                        <i class="ti {{ $searchLocation->is_active ? 'ti-power text-danger' : 'ti-power text-success' }} me-2 dropdown-item-icon" aria-hidden="true"></i>
                                                        {{ $searchLocation->is_active ? 'Désactiver' : 'Activer' }}
                                                    </button>
                                                    <div class="dropdown-divider"></div>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modal-search-location-delete-{{ $searchLocation->uuid }}"
                                                    >
                                                        <i class="ti ti-trash me-2 dropdown-item-icon text-danger" aria-hidden="true"></i>
                                                        Supprimer
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-secondary py-5" colspan="6">
                                        Aucune localisation configurée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALES AJOUT & MODIFICATION -->
    @push('modals')
        <!-- AJOUTER UN TYPE DE TITRE -->
        <div class="modal modal-blur fade" id="add-title-type" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form class="modal-content" method="post" action="{{ route('registry.settings.title-types.store') }}" autocomplete="off" novalidate>
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="ti ti-certificate me-2 text-primary" aria-hidden="true"></i>
                            Ajouter un type de titre
                        </h3>
                        <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        @include('registry.settings._title_type_form', ['titleType' => null])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Annuler</button>
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-check me-1" aria-hidden="true"></i>
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- AJOUTER UNE LOCALISATION -->
        <div class="modal modal-blur fade" id="add-search-location" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form class="modal-content" method="post" action="{{ route('registry.settings.search-locations.store') }}" autocomplete="off" novalidate>
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="ti ti-map-pin me-2 text-primary" aria-hidden="true"></i>
                            Ajouter une localisation
                        </h3>
                        <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        @include('registry.settings._search_location_form', ['searchLocation' => null])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Annuler</button>
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-check me-1" aria-hidden="true"></i>
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODALES D'EDITION TYPES DE TITRES -->
        @foreach ($titleTypes as $titleType)
            @if ($titleType->trashed())
                <!-- MODALE RESTAURATION TYPE DE TITRE -->
                <div class="modal modal-blur fade" id="modal-title-type-restore-{{ $titleType->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            <div class="modal-body text-center py-4">
                                <i class="ti ti-restore text-success fs-1 mb-2"></i>
                                <h3>Restaurer le type de titre ?</h3>
                                <div class="text-secondary small">
                                    Le type de titre <strong>{{ $titleType->title_type_name }} ({{ $titleType->title_type_code }})</strong> sera réintégré avec le statut <strong>Inactif</strong>. Vous pourrez ensuite le réactiver manuellement.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="w-100">
                                    <div class="row">
                                        <div class="col">
                                            <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
                                                Annuler
                                            </button>
                                        </div>
                                        <div class="col">
                                            <form method="post" action="{{ route('registry.settings.title-types.restore', $titleType) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-success w-100" type="submit">
                                                    Restaurer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- MODALE MODIFICATION TYPE DE TITRE -->
                <div class="modal modal-blur fade" id="edit-title-type-{{ $titleType->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <form class="modal-content" method="post" action="{{ route('registry.settings.title-types.update', $titleType) }}" autocomplete="off" novalidate>
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h3 class="modal-title">
                                    <i class="ti ti-edit me-2 text-primary" aria-hidden="true"></i>
                                    Modifier le type de titre
                                </h3>
                                <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                @include('registry.settings._title_type_form', ['titleType' => $titleType])
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Annuler</button>
                                <button class="btn btn-primary" type="submit">
                                    <i class="ti ti-device-floppy me-1" aria-hidden="true"></i>
                                    Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODALE CONFIRMATION STATUT TYPE DE TITRE -->
                <div class="modal modal-blur fade" id="modal-title-type-status-{{ $titleType->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            <div class="modal-body text-center py-4">
                                <i class="ti {{ $titleType->is_active ? 'ti-power text-danger' : 'ti-power text-success' }} fs-1 mb-2"></i>
                                <h3>{{ $titleType->is_active ? 'Désactiver le type de titre ?' : 'Activer le type de titre ?' }}</h3>
                                <div class="text-secondary small">
                                    @if ($titleType->is_active)
                                        Le type de titre <strong>{{ $titleType->title_type_name }}</strong> ne sera plus sélectionnable pour les nouveaux dossiers.
                                    @else
                                        Le type de titre <strong>{{ $titleType->title_type_name }}</strong> sera à nouveau disponible lors de l'enregistrement de dossiers.
                                    @endif
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="w-100">
                                    <div class="row">
                                        <div class="col">
                                            <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
                                                Annuler
                                            </button>
                                        </div>
                                        <div class="col">
                                            <form method="post" action="{{ route('registry.settings.title-types.status.update', $titleType) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $titleType->is_active ? '0' : '1' }}">
                                                <button class="btn {{ $titleType->is_active ? 'btn-danger' : 'btn-success' }} w-100" type="submit">
                                                    Confirmer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODALE SUPPRESSION TYPE DE TITRE -->
                <div class="modal modal-blur fade" id="modal-title-type-delete-{{ $titleType->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            <div class="modal-body text-center py-4">
                                <i class="ti ti-trash text-danger fs-1 mb-2"></i>
                                <h3>Supprimer le type de titre ?</h3>
                                <div class="text-secondary small">
                                    Le type de titre <strong>{{ $titleType->title_type_name }}</strong> sera marqué comme supprimé (suppression logique). Cette action sera refusée s'il est déjà utilisé par des formulaires enregistrés.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="w-100">
                                    <div class="row">
                                        <div class="col">
                                            <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
                                                Annuler
                                            </button>
                                        </div>
                                        <div class="col">
                                            <form method="post" action="{{ route('registry.settings.title-types.destroy', $titleType) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger w-100" type="submit">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach

        <!-- MODALES D'EDITION LOCALISATIONS -->
        @foreach ($searchLocations as $searchLocation)
            @if ($searchLocation->trashed())
                <!-- MODALE RESTAURATION LOCALISATION -->
                <div class="modal modal-blur fade" id="modal-search-location-restore-{{ $searchLocation->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            <div class="modal-body text-center py-4">
                                <i class="ti ti-restore text-success fs-1 mb-2"></i>
                                <h3>Restaurer la localisation ?</h3>
                                <div class="text-secondary small">
                                    La localisation <strong>{{ $searchLocation->location_name }}</strong> sera réintégrée avec le statut <strong>Inactif</strong>. Vous pourrez ensuite la réactiver manuellement.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="w-100">
                                    <div class="row">
                                        <div class="col">
                                            <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
                                                Annuler
                                            </button>
                                        </div>
                                        <div class="col">
                                            <form method="post" action="{{ route('registry.settings.search-locations.restore', $searchLocation) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-success w-100" type="submit">
                                                    Restaurer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- MODALE MODIFICATION LOCALISATION -->
                <div class="modal modal-blur fade" id="edit-search-location-{{ $searchLocation->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <form class="modal-content" method="post" action="{{ route('registry.settings.search-locations.update', $searchLocation) }}" autocomplete="off" novalidate>
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h3 class="modal-title">
                                    <i class="ti ti-edit me-2 text-primary" aria-hidden="true"></i>
                                    Modifier la localisation
                                </h3>
                                <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                @include('registry.settings._search_location_form', ['searchLocation' => $searchLocation])
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Annuler</button>
                                <button class="btn btn-primary" type="submit">
                                    <i class="ti ti-device-floppy me-1" aria-hidden="true"></i>
                                    Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODALE CONFIRMATION STATUT LOCALISATION -->
                <div class="modal modal-blur fade" id="modal-search-location-status-{{ $searchLocation->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            <div class="modal-body text-center py-4">
                                <i class="ti {{ $searchLocation->is_active ? 'ti-power text-danger' : 'ti-power text-success' }} fs-1 mb-2"></i>
                                <h3>{{ $searchLocation->is_active ? 'Désactiver la localisation ?' : 'Activer la localisation ?' }}</h3>
                                <div class="text-secondary small">
                                    @if ($searchLocation->is_active)
                                        La localisation <strong>{{ $searchLocation->location_name }}</strong> ne pourra plus être sélectionnée pour de nouvelles missions de recherche.
                                    @else
                                        La localisation <strong>{{ $searchLocation->location_name }}</strong> sera à nouveau disponible pour l'affectation de missions de recherche.
                                    @endif
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="w-100">
                                    <div class="row">
                                        <div class="col">
                                            <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
                                                Annuler
                                            </button>
                                        </div>
                                        <div class="col">
                                            <form method="post" action="{{ route('registry.settings.search-locations.status.update', $searchLocation) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $searchLocation->is_active ? '0' : '1' }}">
                                                <button class="btn {{ $searchLocation->is_active ? 'btn-danger' : 'btn-success' }} w-100" type="submit">
                                                    Confirmer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODALE SUPPRESSION LOCALISATION -->
                <div class="modal modal-blur fade" id="modal-search-location-delete-{{ $searchLocation->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            <div class="modal-body text-center py-4">
                                <i class="ti ti-trash text-danger fs-1 mb-2"></i>
                                <h3>Supprimer la localisation ?</h3>
                                <div class="text-secondary small">
                                    La localisation <strong>{{ $searchLocation->location_name }}</strong> sera marquée comme supprimée (suppression logique). Cette action sera refusée si elle est encore utilisée dans l'historique ou par des missions.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="w-100">
                                    <div class="row">
                                        <div class="col">
                                            <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
                                                Annuler
                                            </button>
                                        </div>
                                        <div class="col">
                                            <form method="post" action="{{ route('registry.settings.search-locations.destroy', $searchLocation) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger w-100" type="submit">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endpush
@endsection
