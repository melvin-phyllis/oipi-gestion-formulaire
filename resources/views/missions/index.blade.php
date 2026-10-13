@extends('layouts.app')

@section('title', 'Missions de recherche')

@section('content')
    <!-- EN-TETE DE PAGE -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <h1 class="page-title">
                    {{ $canViewAllMissions ? 'Toutes les missions de recherche' : 'Mes missions de recherche' }}
                </h1>
                <div class="text-secondary mt-1">
                    {{ $canViewAllMissions ? 'Supervision, affectation et suivi opérationnel des recherches physiques.' : 'Consultez vos tâches, démarrez vos missions et consignez vos comptes rendus.' }}
                </div>
            </div>
            @if ($canViewAllMissions)
                <div class="col-auto ms-auto d-print-none">
                    <a class="btn btn-primary" href="{{ route('registry.forms.index') }}">
                        <i class="ti ti-file-search me-1"></i>
                        Affecter depuis le registre
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- FILTRES DE MISSIONS -->
    <div class="card mb-3">
        <div class="card-header py-2">
            <h3 class="card-title fs-5">
                <i class="ti ti-filter me-2 text-primary"></i>
                Filtrer les missions
            </h3>
        </div>
        <div class="card-body py-3">
            <form method="get" action="{{ route('missions.index') }}">
                <div class="row g-2 align-items-end">
                    <!-- RECHERCHE LIBRE -->
                    <div class="col-lg-3">
                        <label class="form-label mb-1" for="search">Recherche</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search text-secondary"></i></span>
                            <input
                                class="form-control"
                                id="search"
                                name="search"
                                type="search"
                                value="{{ request('search') }}"
                                placeholder="N° dossier, n° formulaire..."
                            >
                        </div>
                    </div>

                    <!-- AGENT RESPONSABLE (SI VUE SUPERVISEUR) -->
                    @if ($canViewAllMissions)
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label mb-1" for="responsible_filter">Agent</label>
                            <select class="form-select" id="responsible_filter" name="responsible_user_code_unique">
                                <option value="">Tous les agents</option>
                                @foreach ($responsibleUsers as $user)
                                    <option value="{{ $user->user_code_unique }}" @selected(request('responsible_user_code_unique') === $user->user_code_unique)>
                                        {{ $user->first_names }} {{ $user->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- LOCALISATION -->
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label mb-1" for="location_filter">Localisation</label>
                        <select class="form-select" id="location_filter" name="search_location_code_unique">
                            <option value="">Toutes les zones</option>
                            @foreach ($searchLocations as $location)
                                <option value="{{ $location->search_location_code_unique }}" @selected(request('search_location_code_unique') === $location->search_location_code_unique)>
                                    {{ $location->location_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- STATUT -->
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label mb-1" for="status_filter">Statut</label>
                        <select class="form-select" id="status_filter" name="status">
                            <option value="">Tous les statuts</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                    @if ($status->value === 'assigned') Affectée
                                    @elseif ($status->value === 'in_progress') En cours
                                    @elseif ($status->value === 'completed') Terminée
                                    @elseif ($status->value === 'cancelled') Annulée
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- PRIORITE -->
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

                    <!-- BOUTONS -->
                    <div class="col-lg-1 d-flex gap-2">
                        <button class="btn btn-primary flex-fill" type="submit" title="Filtrer">
                            <i class="ti ti-check"></i>
                        </button>
                        @if (request()->hasAny(['search', 'responsible_user_code_unique', 'search_location_code_unique', 'status', 'priority', 'overdue_only']))
                            <a class="btn btn-outline-secondary" href="{{ route('missions.index') }}" title="Réinitialiser">
                                <i class="ti ti-x"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- SOUS-FILTRE EN RETARD -->
                <div class="d-flex align-items-center gap-3 mt-2 pt-2 border-top">
                    <label class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="checkbox" name="overdue_only" value="1" @checked(request('overdue_only') === '1') onchange="this.form.submit()">
                        <span class="form-check-label text-danger fw-medium">
                            <i class="ti ti-alert-triangle me-1"></i>
                            Afficher uniquement les missions en retard
                        </span>
                    </label>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLEAU DES MISSIONS -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Dossier / N° Registre</th>
                        @if ($canViewAllMissions)
                            <th>Agent affecté</th>
                        @endif
                        <th>Zone de recherche</th>
                        <th>Échéance</th>
                        <th>Priorité</th>
                        <th>Statut</th>
                        <th>Résultat</th>
                        <th class="w-1 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($missions as $mission)
                        @php
                            $isOverdue = $mission->due_date && $mission->due_date->isPast() && $mission->status->isActive();
                        @endphp
                        <tr class="{{ $isOverdue ? 'bg-danger-lt' : '' }}">
                            <td>
                                <div>
                                    <a href="{{ route('missions.show', $mission) }}" class="fw-bold text-primary font-monospace">
                                        {{ $mission->missingForm?->registry_number }}
                                    </a>
                                </div>
                                <div class="text-secondary small">
                                    {{ $mission->missingForm?->titleType?->title_type_code }} · {{ $mission->missingForm?->form_number }}
                                </div>
                            </td>

                            @if ($canViewAllMissions)
                                <td>
                                    <div class="fw-medium">{{ $mission->responsible?->first_names }} {{ $mission->responsible?->last_name }}</div>
                                    <div class="text-secondary small">{{ $mission->responsible?->service?->service_name ?? 'OIPI' }}</div>
                                </td>
                            @endif

                            <td>
                                <span class="badge bg-light text-secondary border">
                                    <i class="ti ti-map-pin me-1"></i>
                                    {{ $mission->searchLocation?->location_name ?? 'Non spécifiée' }}
                                </span>
                            </td>

                            <td>
                                @if ($mission->due_date)
                                    <span class="{{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                                        {{ $mission->due_date->format('d/m/Y') }}
                                        @if ($isOverdue)
                                            <i class="ti ti-alert-triangle ms-1 text-danger" title="Mission en retard"></i>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-secondary">Aucune</span>
                                @endif
                            </td>

                            <td>
                                @if ($mission->priority->value === 'urgent')
                                    <span class="badge bg-red text-white">Urgente</span>
                                @elseif ($mission->priority->value === 'high')
                                    <span class="badge bg-orange text-white">Haute</span>
                                @elseif ($mission->priority->value === 'normal')
                                    <span class="badge bg-secondary-lt text-secondary">Normale</span>
                                @else
                                    <span class="badge bg-secondary-lt">Basse</span>
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
                                    Ouvrir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-secondary py-5" colspan="{{ $canViewAllMissions ? '8' : '7' }}">
                                <div class="empty">
                                    <div class="empty-icon text-muted">
                                        <i class="ti ti-target-arrow fs-1"></i>
                                    </div>
                                    <p class="empty-title">Aucune mission trouvée</p>
                                    <p class="empty-subtitle text-secondary">
                                        {{ $canViewAllMissions ? 'Aucune mission ne correspond aux critères de recherche.' : 'Vous n\'avez actuellement aucune mission de recherche affectée.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($missions->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $missions->links() }}
            </div>
        @endif
    </div>
@endsection

