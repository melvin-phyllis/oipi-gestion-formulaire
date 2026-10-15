@extends('layouts.app')

@section('title', 'Directions et services')

@section('content')
    <!-- PAGE HEADER TABLER -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Paramètres / Organigramme</div>
                <h1 class="page-title d-flex align-items-center gap-2">
                    <i class="ti ti-sitemap text-primary"></i>
                    <span>Directions et services</span>
                </h1>
                <div class="text-secondary mt-1">
                    Structure organisationnelle de l’OIPI pour l'affectation des missions et des agents.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a class="btn btn-primary d-none d-sm-inline-block" href="{{ route('organization.directorates.create') }}">
                    <i class="ti ti-building-plus me-1" aria-hidden="true"></i>
                    Ajouter une direction
                </a>
                <a class="btn btn-primary d-sm-none btn-icon" href="{{ route('organization.directorates.create') }}" aria-label="Ajouter une direction">
                    <i class="ti ti-plus" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>

    @if ($directorates->isEmpty())
        <!-- ETAT VIDE SI AUCUNE DIRECTION -->
        <div class="card">
            <div class="card-body text-center text-secondary py-5">
                <div class="empty">
                    <div class="empty-icon text-muted">
                        <i class="ti ti-building-off fs-1"></i>
                    </div>
                    <p class="empty-title">Aucune direction configurée</p>
                    <p class="empty-subtitle text-secondary">
                        Commencez par structurer l’organigramme en créant la première direction de l'OIPI.
                    </p>
                    <div class="empty-action">
                        <a href="{{ route('organization.directorates.create') }}" class="btn btn-primary">
                            <i class="ti ti-building-plus me-1" aria-hidden="true"></i>
                            Ajouter une direction
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- CARTE PRINCIPALE AVEC ONGLETS PAR DIRECTION -->
        <div class="card">
            <!-- BARRE D'ONGLETS HORIZONTALE -->
            <div class="card-header border-bottom py-0 px-3 bg-light-subtle">
                <ul class="nav nav-tabs card-header-tabs flex-nowrap overflow-auto" id="directorate-tabs-list" role="tablist" style="scrollbar-width: thin;">
                    @foreach ($directorates as $directorate)
                        <li class="nav-item" role="presentation">
                            <a
                                href="#directorate-{{ $directorate->uuid }}"
                                class="nav-link py-3 px-3 d-flex align-items-center gap-2 {{ $loop->first ? 'active' : '' }}"
                                id="tab-btn-{{ $directorate->uuid }}"
                                data-bs-toggle="tab"
                                data-bs-target="#directorate-{{ $directorate->uuid }}"
                                role="tab"
                                aria-controls="directorate-{{ $directorate->uuid }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            >
                                <i class="ti ti-building {{ $directorate->trashed() ? 'text-danger' : ($directorate->is_active ? 'text-primary' : 'text-secondary') }}"></i>
                                <span class="fw-semibold">{{ $directorate->directorate_code }}</span>
                                <span class="d-none d-lg-inline text-secondary small text-truncate" style="max-width: 180px;">
                                    — {{ $directorate->directorate_name }}
                                </span>
                                <span class="badge {{ $directorate->trashed() ? 'bg-danger-lt text-danger' : ($directorate->is_active ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary') }} ms-1 font-monospace" style="font-size: 0.72rem;">
                                    {{ $directorate->services->count() }} svc
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- CONTENU DE CHAQUE DIRECTION -->
            <div class="tab-content" id="directorate-tabs-content">
                @foreach ($directorates as $directorate)
                    <div
                        class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                        id="directorate-{{ $directorate->uuid }}"
                        role="tabpanel"
                        aria-labelledby="tab-btn-{{ $directorate->uuid }}"
                    >
                        <!-- EN-TETE DETAILLE DE LA DIRECTION SELECTIONNEE -->
                        <div class="p-3 border-bottom bg-light">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    
                                    <div>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="badge {{ $directorate->trashed() ? 'bg-danger-lt text-danger' : ($directorate->is_active ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary') }}">
                                                <span class="badge badge-dot {{ $directorate->trashed() ? 'bg-danger' : ($directorate->is_active ? 'bg-success' : 'bg-secondary') }} me-1"></span>
                                                {{ $directorate->trashed() ? 'Supprimée' : ($directorate->is_active ? 'Active' : 'Inactive') }}
                                            </span>
                                        </div>
                                        <div class="text-secondary small mt-1">
                                            <i class="ti ti-sitemap me-1" aria-hidden="true"></i>
                                            {{ $directorate->services->count() }} service{{ $directorate->services->count() > 1 ? 's' : '' }} rattaché{{ $directorate->services->count() > 1 ? 's' : '' }}
                                            · Ordre d'affichage : <code>#{{ $directorate->display_order }}</code>
                                        </div>
                                    </div>
                                </div>

                                <!-- ACTIONS DE LA DIRECTION -->
                                <div class="d-flex align-items-center gap-2">
                                    @if (! $directorate->trashed() && $directorate->is_active)
                                        <a class="btn btn-sm btn-primary" href="{{ route('organization.services.create', $directorate) }}">
                                            <i class="ti ti-plus me-1" aria-hidden="true"></i>
                                            Ajouter un service
                                        </a>
                                    @endif

                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            @if ($directorate->trashed())
                                                <button
                                                    type="button"
                                                    class="dropdown-item text-success"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modal-directorate-restore-{{ $directorate->uuid }}"
                                                >
                                                    <i class="ti ti-restore me-2 dropdown-item-icon text-success" aria-hidden="true"></i>
                                                    Restaurer la direction
                                                </button>
                                            @else
                                                <a class="dropdown-item" href="{{ route('organization.directorates.edit', $directorate) }}">
                                                    <i class="ti ti-edit me-2 dropdown-item-icon text-primary" aria-hidden="true"></i>
                                                    Modifier la direction
                                                </a>
                                                <button
                                                    type="button"
                                                    class="dropdown-item {{ $directorate->is_active ? 'text-danger' : 'text-success' }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modal-directorate-status-{{ $directorate->uuid }}"
                                                >
                                                    <i class="ti {{ $directorate->is_active ? 'ti-power text-danger' : 'ti-power text-success' }} me-2 dropdown-item-icon" aria-hidden="true"></i>
                                                    {{ $directorate->is_active ? 'Désactiver la direction' : 'Activer la direction' }}
                                                </button>
                                                <div class="dropdown-divider"></div>
                                                <button
                                                    type="button"
                                                    class="dropdown-item text-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modal-directorate-delete-{{ $directorate->uuid }}"
                                                >
                                                    <i class="ti ti-trash me-2 dropdown-item-icon text-danger" aria-hidden="true"></i>
                                                    Supprimer la direction
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TABLEAU DES SERVICES DE LA DIRECTION -->
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Code</th>
                                        <th>Abréviation</th>
                                        <th>Ordre</th>
                                        <th>Statut</th>
                                        <th class="w-1 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($directorate->services as $service)
                                        <tr class="{{ $service->trashed() ? 'opacity-75' : '' }}">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    
                                                    <div>
                                                        <span class="fw-semibold text-dark">{{ $service->service_name }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-lt font-monospace">{{ $service->service_code }}</span>
                                            </td>
                                            <td>
                                                @if ($service->abbreviation)
                                                    <span class="badge bg-light text-secondary border font-monospace">{{ $service->abbreviation }}</span>
                                                @else
                                                    <span class="text-secondary small">—</span>
                                                @endif
                                            </td>
                                            <td class="text-secondary small font-monospace">
                                                #{{ $service->display_order }}
                                            </td>
                                            <td>
                                                @if ($service->trashed())
                                                    <span class="status status-danger">
                                                        <span class="status-dot"></span>
                                                        Supprimé
                                                    </span>
                                                @elseif ($service->is_active)
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
                                                    <button class="btn btn-action dropdown-toggle align-text-top" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions service">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        @if ($service->trashed())
                                                            <button
                                                                type="button"
                                                                class="dropdown-item text-success"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#modal-service-restore-{{ $service->uuid }}"
                                                            >
                                                                <i class="ti ti-restore me-2 dropdown-item-icon text-success" aria-hidden="true"></i>
                                                                Restaurer
                                                            </button>
                                                        @else
                                                            <a class="dropdown-item" href="{{ route('organization.services.edit', $service) }}">
                                                                <i class="ti ti-edit me-2 dropdown-item-icon text-primary" aria-hidden="true"></i>
                                                                Modifier
                                                            </a>
                                                            <button
                                                                type="button"
                                                                class="dropdown-item {{ $service->is_active ? 'text-danger' : 'text-success' }}"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#modal-service-status-{{ $service->uuid }}"
                                                            >
                                                                <i class="ti {{ $service->is_active ? 'ti-power text-danger' : 'ti-power text-success' }} me-2 dropdown-item-icon" aria-hidden="true"></i>
                                                                {{ $service->is_active ? 'Désactiver' : 'Activer' }}
                                                            </button>
                                                            <div class="dropdown-divider"></div>
                                                            <button
                                                                type="button"
                                                                class="dropdown-item text-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#modal-service-delete-{{ $service->uuid }}"
                                                            >
                                                                <i class="ti ti-trash me-2 dropdown-item-icon text-danger" aria-hidden="true"></i>
                                                                Supprimer
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        @push('modals')
                                            @if ($service->trashed())
                                                <!-- MODALE RESTAURATION SERVICE -->
                                                <div class="modal modal-blur fade" id="modal-service-restore-{{ $service->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                                                        <div class="modal-content">
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                            <div class="modal-body text-center py-4">
                                                                <i class="ti ti-restore text-success fs-1 mb-2"></i>
                                                                <h3>Restaurer le service ?</h3>
                                                                <div class="text-secondary small">
                                                                    Le service <strong>{{ $service->service_name }}</strong> sera réintégré avec le statut <strong>Inactif</strong>. Vous pourrez ensuite le réactiver pour y affecter des agents.
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
                                                                            <form method="post" action="{{ route('organization.services.restore', $service) }}">
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
                                                <!-- MODALE CONFIRMATION STATUT SERVICE -->
                                                <div class="modal modal-blur fade" id="modal-service-status-{{ $service->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                                                        <div class="modal-content">
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                            <div class="modal-body text-center py-4">
                                                                <i class="ti {{ $service->is_active ? 'ti-power text-danger' : 'ti-power text-success' }} fs-1 mb-2"></i>
                                                                <h3>{{ $service->is_active ? 'Désactiver le service ?' : 'Activer le service ?' }}</h3>
                                                                <div class="text-secondary small">
                                                                    @if ($service->is_active)
                                                                        Le service <strong>{{ $service->service_name }}</strong> ne pourra être désactivé que si aucun agent actif n'y est actuellement rattaché.
                                                                    @else
                                                                        Le service <strong>{{ $service->service_name }}</strong> sera à nouveau disponible pour l'affectation d'agents.
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
                                                                            <form method="post" action="{{ route('organization.services.status.update', $service) }}">
                                                                                @csrf
                                                                                @method('PATCH')
                                                                                <input name="is_active" type="hidden" value="{{ $service->is_active ? '0' : '1' }}">
                                                                                <button class="btn {{ $service->is_active ? 'btn-danger' : 'btn-success' }} w-100" type="submit">
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

                                                <!-- MODALE SUPPRESSION SERVICE -->
                                                <div class="modal modal-blur fade" id="modal-service-delete-{{ $service->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                                                        <div class="modal-content">
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                            <div class="modal-body text-center py-4">
                                                                <i class="ti ti-trash text-danger fs-1 mb-2"></i>
                                                                <h3>Supprimer le service ?</h3>
                                                                <div class="text-secondary small">
                                                                    Le service <strong>{{ $service->service_name }}</strong> sera marqué comme supprimé. Cette action sera refusée si des comptes utilisateurs y sont encore rattachés.
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
                                                                            <form method="post" action="{{ route('organization.services.destroy', $service) }}">
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
                                        @endpush
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-secondary py-5">
                                                <div class="empty">
                                                    <div class="empty-icon text-muted">
                                                        <i class="ti ti-folder-off fs-1"></i>
                                                    </div>
                                                    <p class="empty-title">Aucun service rattaché</p>
                                                    <p class="empty-subtitle text-secondary">
                                                        Cette direction ne comporte aucun service actif ou inactif pour le moment.
                                                    </p>
                                                    @if (! $directorate->trashed() && $directorate->is_active)
                                                        <div class="empty-action">
                                                             <a class="btn btn-sm btn-primary" href="{{ route('organization.services.create', $directorate) }}">
                                                                <i class="ti ti-plus me-1" aria-hidden="true"></i>
                                                                Ajouter un premier service
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @push('modals')
                        @if ($directorate->trashed())
                            <!-- MODALE RESTAURATION DIRECTION -->
                            <div class="modal modal-blur fade" id="modal-directorate-restore-{{ $directorate->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                        <div class="modal-body text-center py-4">
                                            <i class="ti ti-restore text-success fs-1 mb-2"></i>
                                            <h3>Restaurer la direction ?</h3>
                                            <div class="text-secondary small">
                                                La direction <strong>{{ $directorate->directorate_name }}</strong> sera réintégrée avec le statut <strong>Inactif</strong>. Vous pourrez ensuite la réactiver manuellement.
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
                                                        <form method="post" action="{{ route('organization.directorates.restore', $directorate) }}">
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
                            <!-- MODALE CONFIRMATION STATUT DIRECTION -->
                            <div class="modal modal-blur fade" id="modal-directorate-status-{{ $directorate->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                        <div class="modal-body text-center py-4">
                                            <i class="ti {{ $directorate->is_active ? 'ti-power text-danger' : 'ti-power text-success' }} fs-1 mb-2"></i>
                                            <h3>{{ $directorate->is_active ? 'Désactiver la direction ?' : 'Activer la direction ?' }}</h3>
                                            <div class="text-secondary small">
                                                @if ($directorate->is_active)
                                                    La direction <strong>{{ $directorate->directorate_name }}</strong> ne pourra être désactivée que si tous ses services subordonnés sont préalablement désactivés.
                                                @else
                                                    La direction <strong>{{ $directorate->directorate_name }}</strong> sera réactivée.
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
                                                        <form method="post" action="{{ route('organization.directorates.status.update', $directorate) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input name="is_active" type="hidden" value="{{ $directorate->is_active ? '0' : '1' }}">
                                                            <button class="btn {{ $directorate->is_active ? 'btn-danger' : 'btn-success' }} w-100" type="submit">
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

                            <!-- MODALE SUPPRESSION DIRECTION -->
                            <div class="modal modal-blur fade" id="modal-directorate-delete-{{ $directorate->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                        <div class="modal-body text-center py-4">
                                            <i class="ti ti-trash text-danger fs-1 mb-2"></i>
                                            <h3>Supprimer la direction ?</h3>
                                            <div class="text-secondary small">
                                                La direction <strong>{{ $directorate->directorate_name }}</strong> sera marquée comme supprimée. Cette action sera refusée si elle contient encore des services rattachés.
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
                                                        <form method="post" action="{{ route('organization.directorates.destroy', $directorate) }}">
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
                    @endpush
                @endforeach
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script nonce="{{ Vite::cspNonce() }}">
        document.addEventListener('DOMContentLoaded', function () {
            // Activation automatique de l'onglet selon le hash URL (ex. #directorate-uuid)
            function activateDirectorateTabFromHash() {
                const hash = window.location.hash;
                if (hash && hash.startsWith('#directorate-')) {
                    const targetTabBtn = document.querySelector('a[data-bs-toggle="tab"][href="' + hash + '"]');
                    if (targetTabBtn && window.bootstrap && window.bootstrap.Tab) {
                        const tab = new window.bootstrap.Tab(targetTabBtn);
                        tab.show();
                    }
                }
            }

            activateDirectorateTabFromHash();
            window.addEventListener('hashchange', activateDirectorateTabFromHash);

            // Mémorisation du hash lors du clic sur un onglet
            const tabLinks = document.querySelectorAll('#directorate-tabs-list a[data-bs-toggle="tab"]');
            tabLinks.forEach(function (link) {
                link.addEventListener('shown.bs.tab', function (e) {
                    const targetHref = e.target.getAttribute('href');
                    if (targetHref && history.replaceState) {
                        history.replaceState(null, null, targetHref);
                    }
                });
            });
        });
    </script>
@endpush
