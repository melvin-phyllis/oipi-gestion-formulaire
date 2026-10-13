@extends('layouts.app')

@section('title', 'Gestion des utilisateurs')

@section('content')
    <!-- PAGE HEADER TABLER -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Administration</div>
                <h1 class="page-title">Utilisateurs</h1>
                <div class="text-secondary mt-1">Gérez les accès, rôles et rattachements organisationnels des agents de l'OIPI.</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a class="btn btn-primary d-none d-sm-inline-block" href="{{ route('users.create') }}">
                    <i class="ti ti-plus me-1" aria-hidden="true"></i>
                    Ajouter un utilisateur
                </a>
                <a class="btn btn-primary d-sm-none btn-icon" href="{{ route('users.create') }}" aria-label="Ajouter un utilisateur">
                    <i class="ti ti-plus" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- FILTRES DE RECHERCHE -->
    <div class="card mb-3">
        <div class="card-header py-2">
            <h3 class="card-title fs-5">
                <i class="ti ti-filter me-2 text-primary" aria-hidden="true"></i>
                Filtrer les comptes
            </h3>
        </div>
        <div class="card-body py-3">
            <form method="get" action="{{ route('users.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label mb-1" for="search">Recherche</label>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-secondary"></i>
                            </span>
                            <input
                                class="form-control"
                                id="search"
                                name="search"
                                type="search"
                                value="{{ request('search') }}"
                                placeholder="Nom, identifiant ou e-mail..."
                            >
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label mb-1" for="role_filter">Rôle</label>
                        <select class="form-select" id="role_filter" name="role_code_unique">
                            <option value="">Tous les rôles</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->role_code_unique }}" @selected(request('role_code_unique') === $role->role_code_unique)>
                                    {{ $role->role_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label mb-1" for="status_filter">État du compte</label>
                        <select class="form-select" id="status_filter" name="is_active">
                            <option value="">Tous les états</option>
                            <option value="1" @selected(request('is_active') === '1')>Actifs uniquement</option>
                            <option value="0" @selected(request('is_active') === '0')>Inactifs uniquement</option>
                        </select>
                    </div>
                    <div class="col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary flex-fill" type="submit">
                            <i class="ti ti-filter-check me-1" aria-hidden="true"></i>
                            Filtrer
                        </button>
                        @if (request()->hasAny(['search', 'role_code_unique', 'is_active']))
                            <a class="btn btn-outline-secondary" href="{{ route('users.index') }}" title="Réinitialiser les filtres">
                                <i class="ti ti-x" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLEAU DES UTILISATEURS -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Rattachement</th>
                        <th>Statut</th>
                        <th class="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $managedUser)
                        @php
                            $userInitials = mb_strtoupper(mb_substr($managedUser->first_names, 0, 1) . mb_substr($managedUser->last_name, 0, 1));
                            $isSelf = $managedUser->is(auth()->user());
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    
                                    <div class="d-flex flex-column">
                                        <div class="fw-medium text-dark">
                                            {{ $managedUser->first_names }} {{ $managedUser->last_name }}
                                            @if ($isSelf)
                                                <span class="badge bg-primary-lt text-primary ms-1">Vous</span>
                                            @endif
                                        </div>
                                        <div class="text-secondary small">
                                            <span class="fw-medium text-muted">@lang(''){{ $managedUser->username }}</span> · {{ $managedUser->email }}
                                            @if ($managedUser->phone)
                                                · <span class="text-muted">{{ $managedUser->phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($managedUser->role?->role_code === 'SYSTEM_ADMIN')
                                    <span class="badge bg-red-lt">{{ $managedUser->role->role_name }}</span>
                                @elseif ($managedUser->role?->role_code === 'REGISTRY_MANAGER')
                                    <span class="badge bg-orange-lt text-orange">{{ $managedUser->role->role_name }}</span>
                                @elseif ($managedUser->role?->role_code === 'SEARCH_AGENT')
                                    <span class="badge bg-teal-lt">{{ $managedUser->role->role_name }}</span>
                                @else
                                    <span class="badge bg-secondary-lt">{{ $managedUser->role?->role_name ?? '—' }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-dark">{{ $managedUser->service?->service_name ?? 'Compte transversal' }}</div>
                                @if ($managedUser->service?->directorate)
                                    <div class="text-secondary small">
                                        <i class="ti ti-building me-1" aria-hidden="true"></i>
                                        {{ $managedUser->service->directorate->directorate_name }} ({{ $managedUser->service->directorate->directorate_code }})
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($managedUser->is_active)
                                    <span class="status status-success">
                                        <span class="status-dot status-dot-animated"></span>
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
                                    <button class="btn btn-action dropdown-toggle align-text-top" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu actions">
                                        <i class="ti ti-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="{{ route('users.edit', $managedUser) }}">
                                            <i class="ti ti-edit me-2 dropdown-item-icon text-primary" aria-hidden="true"></i>
                                            Modifier
                                        </a>

                                        @if (! $isSelf)
                                            <button
                                                type="button"
                                                class="dropdown-item {{ $managedUser->is_active ? 'text-danger' : 'text-success' }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-status-{{ $managedUser->uuid }}"
                                            >
                                                <i class="ti {{ $managedUser->is_active ? 'ti-user-off text-danger' : 'ti-user-check text-success' }} me-2 dropdown-item-icon" aria-hidden="true"></i>
                                                {{ $managedUser->is_active ? 'Désactiver le compte' : 'Activer le compte' }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>

                        @if (! $isSelf)
                            @push('modals')
                                <!-- MODALE CONFIRMATION STATUT TABLER -->
                                <div class="modal modal-blur fade" id="modal-status-{{ $managedUser->uuid }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                            <div class="modal-status {{ $managedUser->is_active ? 'bg-danger' : 'bg-success' }}"></div>
                                            <div class="modal-body text-center py-4">
                                                <i class="ti {{ $managedUser->is_active ? 'ti-alert-triangle text-danger' : 'ti-circle-check text-success' }} fs-1 mb-2"></i>
                                                <h3>{{ $managedUser->is_active ? 'Désactiver le compte ?' : 'Activer le compte ?' }}</h3>
                                                <div class="text-secondary small">
                                                    @if ($managedUser->is_active)
                                                        L'utilisateur <strong>{{ $managedUser->first_names }} {{ $managedUser->last_name }}</strong> ne pourra plus se connecter et ses sessions actives seront révoquées immédiatement.
                                                    @else
                                                        L'utilisateur <strong>{{ $managedUser->first_names }} {{ $managedUser->last_name }}</strong> retrouvera l'accès à son espace de travail.
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
                                                            <form method="post" action="{{ route('users.status.update', $managedUser) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input name="is_active" type="hidden" value="{{ $managedUser->is_active ? '0' : '1' }}">
                                                                <button class="btn {{ $managedUser->is_active ? 'btn-danger' : 'btn-success' }} w-100" type="submit">
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
                            @endpush
                        @endif
                    @empty
                        <tr>
                            <td class="text-center text-secondary py-5" colspan="5">
                                <div class="empty">
                                    <div class="empty-icon text-muted">
                                        <i class="ti ti-users-minus fs-1"></i>
                                    </div>
                                    <p class="empty-title">Aucun utilisateur trouvé</p>
                                    <p class="empty-subtitle text-secondary">
                                        Modifiez vos filtres ou enregistrez un nouvel utilisateur.
                                    </p>
                                    <div class="empty-action">
                                        <a href="{{ route('users.create') }}" class="btn btn-primary">
                                            <i class="ti ti-plus me-1" aria-hidden="true"></i>
                                            Ajouter un compte
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
