@extends('layouts.app')

@section('title', 'Journal d’audit')

@section('content')
    @php
        $actionLabels = collect($actions)->mapWithKeys(fn ($action) => [$action->value => $action->label()]);
        $hasActiveFilters = request()->hasAny(['search', 'category', 'action', 'actor_user_code_unique', 'occurred_from', 'occurred_to']);
    @endphp

    <!-- EN-TETE DE PAGE -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Sécurité et traçabilité</div>
                <h1 class="page-title d-flex align-items-center gap-2">
                    <span>Journal d’audit</span>
                    <span class="badge bg-primary-lt text-primary fs-6 font-monospace">
                        {{ number_format($logs->total(), 0, ',', ' ') }} événement(s)
                    </span>
                </h1>
                <div class="text-secondary mt-1">
                    Historique immuable des connexions, modifications et actions sensibles du système RISFM.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('audit.index') }}" title="Actualiser la liste">
                    <i class="ti ti-refresh me-1"></i>Actualiser
                </a>
            </div>
        </div>
    </div>

    <!-- FORMULAIRE DE FILTRES -->
    <div class="card mb-3">
        <div class="card-header py-2 d-flex align-items-center justify-content-between">
            <h3 class="card-title fs-5 mb-0">
                <i class="ti ti-filter me-2 text-primary"></i>
                Filtrer les événements
            </h3>
            @if ($hasActiveFilters)
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('audit.index') }}">
                    <i class="ti ti-x me-1"></i>Réinitialiser
                </a>
            @endif
        </div>
        <div class="card-body py-3">
            <form method="get" action="{{ route('audit.index') }}">
                <div class="row g-3">
                    <!-- RECHERCHE LIBRE -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label mb-1" for="audit-search">Recherche libre</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search text-secondary"></i></span>
                            <input
                                id="audit-search"
                                name="search"
                                type="search"
                                class="form-control"
                                value="{{ request('search') }}"
                                placeholder="Acteur, cible, description ou IP"
                            >
                        </div>
                    </div>

                    <!-- CATEGORIE -->
                    <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                        <label class="form-label mb-1" for="audit-category">Catégorie</label>
                        <select id="audit-category" name="category" class="form-select">
                            <option value="">Toutes</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->value }}" @selected(request('category') === $category->value)>
                                    {{ $category->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- ACTION -->
                    <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                        <label class="form-label mb-1" for="audit-action">Action métier</label>
                        <select id="audit-action" name="action" class="form-select">
                            <option value="">Toutes</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action->value }}" @selected(request('action') === $action->value)>
                                    {{ $action->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- UTILISATEUR ACTEUR -->
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label mb-1" for="audit-actor">Acteur</label>
                        <select id="audit-actor" name="actor_user_code_unique" class="form-select">
                            <option value="">Tous les utilisateurs</option>
                            @foreach ($actors as $actor)
                                <option value="{{ $actor->actor_user_code_unique }}" @selected(request('actor_user_code_unique') === $actor->actor_user_code_unique)>
                                    {{ $actor->actor_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- DATE DE DEBUT -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label mb-1" for="audit-from">Période du</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-calendar text-secondary"></i></span>
                            <input
                                id="audit-from"
                                type="date"
                                name="occurred_from"
                                class="form-control"
                                value="{{ request('occurred_from') }}"
                            >
                        </div>
                    </div>

                    <!-- DATE DE FIN -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label mb-1" for="audit-to">Au</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-calendar text-secondary"></i></span>
                            <input
                                id="audit-to"
                                type="date"
                                name="occurred_to"
                                class="form-control"
                                value="{{ request('occurred_to') }}"
                            >
                        </div>
                    </div>

                    <!-- ACTIONS BOUTONS -->
                    <div class="col-12 col-lg-6 d-flex align-items-end gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-filter me-1"></i>Appliquer les filtres
                        </button>
                        @if ($hasActiveFilters)
                            <a class="btn btn-outline-secondary" href="{{ route('audit.index') }}">
                                <i class="ti ti-x me-1"></i>Effacer les filtres
                            </a>
                        @endif
                    </div>
                </div>

                @if ($hasActiveFilters)
                    <!-- RAPPEL DES FILTRES ACTIFS -->
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top">
                        <span class="text-secondary small fw-medium">
                            <i class="ti ti-adjustments me-1"></i>Filtres actifs :
                        </span>
                        @if (request('search'))
                            <span class="badge bg-light text-secondary">
                                Recherche : "{{ request('search') }}"
                            </span>
                        @endif
                        @if (request('category'))
                            @php
                                $catObj = collect($categories)->firstWhere('value', request('category'));
                            @endphp
                            <span class="badge bg-light text-secondary">
                                Catégorie : {{ $catObj?->label() ?? request('category') }}
                            </span>
                        @endif
                        @if (request('action'))
                            <span class="badge bg-light text-secondary">
                                Action : {{ $actionLabels->get(request('action'), request('action')) }}
                            </span>
                        @endif
                        @if (request('actor_user_code_unique'))
                            @php
                                $actObj = collect($actors)->firstWhere('actor_user_code_unique', request('actor_user_code_unique'));
                            @endphp
                            <span class="badge bg-light text-secondary">
                                Acteur : {{ $actObj?->actor_name ?? request('actor_user_code_unique') }}
                            </span>
                        @endif
                        @if (request('occurred_from'))
                            <span class="badge bg-light text-secondary">
                                Du : {{ request('occurred_from') }}
                            </span>
                        @endif
                        @if (request('occurred_to'))
                            <span class="badge bg-light text-secondary">
                                Au : {{ request('occurred_to') }}
                            </span>
                        @endif
                    </div>
                @endif
            </form>
        </div>
    </div>

    <!-- TABLEAU DU JOURNAL D'AUDIT -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th style="min-width: 150px;">Date et heure / IP</th>
                        <th style="min-width: 220px;">Action & Événement</th>
                        <th style="min-width: 170px;">Acteur</th>
                        <th style="min-width: 170px;">Cible</th>
                        <th style="min-width: 200px;">Contexte technique</th>
                        <th class="w-1 text-center">Détails</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $actionCode = $log->action_code;
                            $badgeClass = 'bg-secondary-lt text-secondary';
                            $iconClass = 'ti ti-activity';

                            if (str_contains($actionCode, 'failed') || str_contains($actionCode, 'rejected') || str_contains($actionCode, 'cancelled')) {
                                $badgeClass = 'bg-danger-lt text-danger';
                                $iconClass = 'ti ti-alert-triangle';
                            } elseif (str_contains($actionCode, 'updated') || str_contains($actionCode, 'reassigned') || str_contains($actionCode, 'reopened') || str_contains($actionCode, 'changed')) {
                                $badgeClass = 'bg-warning-lt text-warning';
                                $iconClass = 'ti ti-edit';
                            } elseif (str_contains($actionCode, 'succeeded') || str_contains($actionCode, 'created') || str_contains($actionCode, 'registered_as_found') || str_contains($actionCode, 'completed') || str_contains($actionCode, 'confirmed') || str_contains($actionCode, 'restored')) {
                                $badgeClass = 'bg-green-lt text-green';
                                $iconClass = 'ti ti-circle-check';
                            } elseif (str_contains($actionCode, 'assigned') || str_contains($actionCode, 'started')) {
                                $badgeClass = 'bg-primary-lt text-primary';
                                $iconClass = 'ti ti-arrow-right-circle';
                            }

                            $httpMethod = strtoupper($log->http_method ?? 'GET');
                            $methodBadgeClass = match ($httpMethod) {
                                'POST' => 'bg-green-lt text-green',
                                'PATCH', 'PUT' => 'bg-warning-lt text-warning',
                                'DELETE' => 'bg-danger-lt text-danger',
                                default => 'bg-light text-secondary',
                            };
                        @endphp
                        <tr>
                            <!-- DATE ET HEURE / IP -->
                            <td class="text-nowrap">
                                <div class="fw-semibold text-dark">{{ $log->occurred_at->format('d/m/Y') }}</div>
                                <div class="text-secondary small font-monospace">
                                    <i class="ti ti-clock me-1"></i>{{ $log->occurred_at->format('H:i:s') }}
                                </div>
                                @if ($log->ip_address)
                                    <div class="mt-1">
                                        <span class="badge bg-light text-secondary font-monospace" title="Adresse IP de la requête">
                                            <i class="ti ti-network me-1"></i>{{ $log->ip_address }}
                                        </span>
                                    </div>
                                @else
                                    <div class="text-secondary small mt-1">IP indisponible</div>
                                @endif
                            </td>

                            <!-- ACTION & EVENEMENT -->
                            <td>
                                <div class="mb-1">
                                    <span class="badge {{ $badgeClass }}">
                                        <i class="{{ $iconClass }} me-1"></i>{{ $actionLabels->get($log->action_code, $log->action_code) }}
                                    </span>
                                </div>
                                <div class="text-dark small fw-normal">{{ $log->description }}</div>
                                @if ($log->category)
                                    <div class="text-secondary small font-monospace mt-1">
                                        Catégorie : {{ $log->category }}
                                    </div>
                                @endif
                            </td>

                            <!-- ACTEUR -->
                            <td>
                                @if ($log->actor_name)
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-xs bg-primary-lt text-primary fw-bold me-2">
                                            {{ strtoupper(mb_substr($log->actor_name, 0, 2)) }}
                                        </span>
                                        <div>
                                            <div class="fw-medium text-dark">{{ $log->actor_name }}</div>
                                            @if ($log->actor_user_code_unique)
                                                <div class="text-secondary small font-monospace">{{ $log->actor_user_code_unique }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-cpu text-secondary fs-3 me-2"></i>
                                        <div>
                                            <span class="badge bg-secondary-lt text-secondary">Système</span>
                                        </div>
                                    </div>
                                @endif
                            </td>

                            <!-- CIBLE -->
                            <td>
                                @if ($log->subject_label)
                                    <div class="fw-medium text-dark">{{ $log->subject_label }}</div>
                                @else
                                    <div class="text-secondary">—</div>
                                @endif

                                @if ($log->subject_code_unique)
                                    <div class="mt-1">
                                        <span class="badge bg-light text-secondary font-monospace small">
                                            <i class="ti ti-tag me-1"></i>{{ $log->subject_code_unique }}
                                        </span>
                                    </div>
                                @endif

                                @if ($log->subject_type)
                                    <div class="text-secondary small mt-1">
                                        {{ class_basename($log->subject_type) }}
                                    </div>
                                @endif
                            </td>

                            <!-- CONTEXTE TECHNIQUE -->
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge {{ $methodBadgeClass }} font-monospace">{{ $httpMethod }}</span>
                                    <span class="font-monospace small text-secondary text-truncate" style="max-width: 180px;" title="{{ $log->request_path }}">
                                        {{ $log->request_path }}
                                    </span>
                                </div>
                                @if ($log->route_name)
                                    <div class="text-secondary small font-monospace mt-1" title="Route nommée : {{ $log->route_name }}">
                                        <i class="ti ti-route me-1"></i>{{ $log->route_name }}
                                    </div>
                                @endif
                            </td>

                            <!-- DETAILS / METADONNEES -->
                            <td class="text-center">
                                @if ($log->metadata)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-meta-{{ $log->id }}"
                                        title="Voir les métadonnées techniques"
                                    >
                                        <i class="ti ti-code me-1"></i>Détails
                                    </button>
                                @else
                                    <span class="text-secondary small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty">
                                    <div class="empty-icon">
                                        <i class="ti ti-shield-search fs-1 text-secondary"></i>
                                    </div>
                                    <p class="empty-title fs-3 fw-bold">Aucune activité enregistrée</p>
                                    <p class="empty-subtitle text-secondary">
                                        Aucun événement du journal d'audit ne correspond aux critères de filtre spécifiés.
                                    </p>
                                    @if ($hasActiveFilters)
                                        <div class="empty-action">
                                            <a class="btn btn-primary" href="{{ route('audit.index') }}">
                                                <i class="ti ti-rotate me-1"></i>Réinitialiser les filtres
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

        <!-- PIED DE CARTE / PAGINATION -->
        @if ($logs->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $logs->links() }}
            </div>
        @elseif ($logs->total() > 0)
            <div class="card-footer d-flex align-items-center py-2">
                <div class="text-secondary small">
                    Affichage de <strong class="text-dark">{{ $logs->firstItem() }}</strong> à <strong class="text-dark">{{ $logs->lastItem() }}</strong> sur <strong class="text-dark">{{ number_format($logs->total(), 0, ',', ' ') }}</strong> {{ $logs->total() > 1 ? 'résultats' : 'résultat' }}
                </div>
            </div>
        @endif
    </div>
@endsection

@push('modals')
    @foreach ($logs as $log)
        @if ($log->metadata)
            <div class="modal modal-blur fade" id="modal-meta-{{ $log->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title d-flex align-items-center gap-2">
                                <i class="ti ti-shield-check text-primary fs-3"></i>
                                <span>Détails de l'événement d'audit</span>
                                <span class="badge bg-light text-secondary font-monospace">{{ $log->audit_log_code_unique }}</span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-sm-6">
                                    <div class="text-secondary small">Horodatage</div>
                                    <div class="fw-semibold text-dark">{{ $log->occurred_at->format('d/m/Y H:i:s') }}</div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="text-secondary small">Action & Catégorie</div>
                                    <div class="fw-semibold text-dark">
                                        {{ $actionLabels->get($log->action_code, $log->action_code) }}
                                        <span class="badge bg-light text-secondary font-monospace ms-1">{{ $log->category }}</span>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="text-secondary small">Acteur</div>
                                    <div class="fw-semibold text-dark">
                                        {{ $log->actor_name ?? 'Système' }}
                                        @if ($log->actor_user_code_unique)
                                            <span class="font-monospace small text-secondary">({{ $log->actor_user_code_unique }})</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="text-secondary small">Adresse IP</div>
                                    <div class="fw-semibold font-monospace text-dark">{{ $log->ip_address ?? 'Indisponible' }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-secondary small">Requête HTTP</div>
                                    <div class="font-monospace small bg-light p-2 rounded text-dark mt-1">
                                        <span class="fw-bold">{{ $log->http_method }}</span> {{ $log->request_path }}
                                        @if ($log->route_name)
                                            <span class="text-secondary ms-2">· Route : {{ $log->route_name }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if ($log->user_agent)
                                    <div class="col-12">
                                        <div class="text-secondary small">Agent utilisateur (User Agent)</div>
                                        <div class="small text-secondary font-monospace text-break bg-light p-2 rounded mt-1">
                                            {{ $log->user_agent }}
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label mb-0 fw-semibold text-dark">
                                    <i class="ti ti-brackets-contain me-1 text-primary"></i>Métadonnées techniques (JSON)
                                </label>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    onclick="navigator.clipboard.writeText(document.getElementById('meta-json-{{ $log->id }}').innerText)"
                                >
                                    <i class="ti ti-copy me-1"></i>Copier le JSON
                                </button>
                            </div>
                            <pre id="meta-json-{{ $log->id }}" class="bg-dark text-light p-3 rounded font-monospace small mb-0" style="max-height: 280px; overflow-y: auto;">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endpush
