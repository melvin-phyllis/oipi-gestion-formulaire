@extends('layouts.app')

@section('title', 'Statistiques & Pilotage')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <h1 class="page-title d-flex align-items-center gap-2">
                    <i class="ti ti-chart-bar text-primary" aria-hidden="true"></i>
                    Statistiques & Pilotage du registre
                </h1>
                <div class="text-secondary mt-1">
                    Analyse des performances de recherche, du taux de résolution et de l'avancement des dossiers manquants.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex flex-wrap gap-2">
                <a href="{{ route('analytics.export.excel', request()->query()) }}" class="btn btn-outline-primary">
                    <i class="ti ti-file-spreadsheet me-1" aria-hidden="true"></i>
                    Excel
                </a>
                <a href="{{ route('analytics.export.pdf', request()->query()) }}" class="btn btn-outline-primary">
                    <i class="ti ti-file-type-pdf me-1" aria-hidden="true"></i>
                    PDF
                </a>
                <a href="{{ route('analytics.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-rotate me-1" aria-hidden="true"></i>
                    Réinitialiser les filtres
                </a>
            </div>
        </div>
    </div>

    <!-- CARTE DES FILTRES DE PILOTAGE -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-surface py-3 d-flex align-items-center justify-content-between">
            <h3 class="card-title text-dark mb-0 d-flex align-items-center gap-2">
                <i class="ti ti-filter text-primary" aria-hidden="true"></i>
                Filtres d'analyse
            </h3>
            <span class="badge bg-primary-lt">
                <i class="ti ti-calendar me-1" aria-hidden="true"></i>
                Période basée sur la date d'enregistrement au registre
            </span>
        </div>
        <div class="card-body">
            <form method="get" action="{{ route('analytics.index') }}">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="filter-registered-from">Enregistré du</label>
                        <input
                            type="date"
                            name="registered_from"
                            id="filter-registered-from"
                            class="form-control @error('registered_from') is-invalid @enderror"
                            value="{{ request('registered_from') }}"
                        >
                        @error('registered_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="filter-registered-to">Au</label>
                        <input
                            type="date"
                            name="registered_to"
                            id="filter-registered-to"
                            class="form-control @error('registered_to') is-invalid @enderror"
                            value="{{ request('registered_to') }}"
                        >
                        @error('registered_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="filter-title-type">Type de titre</label>
                        <select name="title_type_code_unique" id="filter-title-type" class="form-select @error('title_type_code_unique') is-invalid @enderror">
                            <option value="">Tous les types</option>
                            @foreach ($titleTypes as $titleType)
                                <option value="{{ $titleType->title_type_code_unique }}" @selected(request('title_type_code_unique') === $titleType->title_type_code_unique)>
                                    {{ $titleType->title_type_name }} ({{ $titleType->title_type_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('title_type_code_unique')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="filter-form-year">Année du titre</label>
                        <input
                            type="number"
                            name="form_year"
                            id="filter-form-year"
                            class="form-control @error('form_year') is-invalid @enderror"
                            min="1900"
                            max="{{ now()->year }}"
                            placeholder="Ex. {{ now()->year }}"
                            value="{{ request('form_year') }}"
                        >
                        @error('form_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="filter-status">Statut du dossier</label>
                        <select name="status" id="filter-status" class="form-select @error('status') is-invalid @enderror">
                            <option value="">Tous les statuts</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="filter-priority">Priorité</label>
                        <select name="priority" id="filter-priority" class="form-select @error('priority') is-invalid @enderror">
                            <option value="">Toutes les priorités</option>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>
                                    {{ $priority->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="filter-location">Localisation</label>
                        <select name="search_location_code_unique" id="filter-location" class="form-select @error('search_location_code_unique') is-invalid @enderror">
                            <option value="">Toutes les localisations</option>
                            @foreach ($searchLocations as $location)
                                <option value="{{ $location->search_location_code_unique }}" @selected(request('search_location_code_unique') === $location->search_location_code_unique)>
                                    {{ $location->location_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('search_location_code_unique')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="filter-responsible">Agent / Responsable</label>
                        <select name="responsible_user_code_unique" id="filter-responsible" class="form-select @error('responsible_user_code_unique') is-invalid @enderror">
                            <option value="">Tous les responsables</option>
                            @foreach ($responsibleUsers as $user)
                                <option value="{{ $user->user_code_unique }}" @selected(request('responsible_user_code_unique') === $user->user_code_unique)>
                                    {{ $user->last_name }} {{ $user->first_names }}
                                </option>
                            @endforeach
                        </select>
                        @error('responsible_user_code_unique')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="filter-trend-months">Évolution mensuelle</label>
                        <select name="trend_months" id="filter-trend-months" class="form-select @error('trend_months') is-invalid @enderror">
                            @foreach ($trendPeriods as $period)
                                <option value="{{ $period }}" @selected((int) request('trend_months', 12) === $period)>
                                    Derniers {{ $period }} mois
                                </option>
                            @endforeach
                        </select>
                        @error('trend_months')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-sm-6 col-lg-9 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-search me-1" aria-hidden="true"></i>
                            Appliquer les filtres
                        </button>
                        <a href="{{ route('analytics.index') }}" class="btn btn-outline-secondary">
                            Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- INDICATEURS CLES DYNAMIQUES DU TABLEAU DE BORD (COMPOSANTS TABLER CARD-STAMP) -->
    <div class="row row-deck row-cards mb-4">
        <!-- 1. Campagne active -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-stamp">
                    <div class="card-stamp-icon bg-primary">
                        <i class="ti ti-files" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="card-body">
                    <div class="subheader text-secondary">Campagne active</div>
                    <div class="h1 m-0 text-dark">{{ number_format($kpis['activeCampaignForms'], 0, ',', ' ') }}</div>
                    <div class="d-flex align-items-center justify-content-between text-secondary small mt-3 pt-2 border-top">
                        <span>Total : <strong>{{ number_format($kpis['totalForms'], 0, ',', ' ') }}</strong> dossier(s)</span>
                        <span class="badge bg-secondary-lt">{{ $kpis['archivedForms'] }} archivé(s)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Taux de résolution -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-stamp">
                    <div class="card-stamp-icon bg-green">
                        <i class="ti ti-circle-check" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="card-body">
                    <div class="subheader text-secondary">Taux de résolution</div>
                    <div class="h1 m-0 text-green">{{ $kpis['resolutionRate'] }}%</div>
                    <div class="progress progress-sm mt-3">
                        <div
                            class="progress-bar bg-green"
                            style="width: {{ min($kpis['resolutionRate'], 100) }}%"
                            role="progressbar"
                            aria-valuenow="{{ $kpis['resolutionRate'] }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-label="Taux de résolution"
                        ></div>
                    </div>
                    <div class="text-secondary small mt-2">
                        <strong>{{ number_format($kpis['resolvedForms'], 0, ',', ' ') }}</strong> dossier(s) résolu(s)
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Taux de finalisation (Saisi) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-stamp">
                    <div class="card-stamp-icon bg-teal">
                        <i class="ti ti-file-certificate" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="card-body">
                    <div class="subheader text-secondary">Taux de saisie ERP</div>
                    <div class="h1 m-0 text-teal">{{ $kpis['completionRate'] }}%</div>
                    <div class="progress progress-sm mt-3">
                        <div
                            class="progress-bar bg-teal"
                            style="width: {{ min($kpis['completionRate'], 100) }}%"
                            role="progressbar"
                            aria-valuenow="{{ $kpis['completionRate'] }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-label="Taux de finalisation"
                        ></div>
                    </div>
                    <div class="text-secondary small mt-2">
                        <strong>{{ number_format($kpis['enteredForms'], 0, ',', ' ') }}</strong> dossier(s) entièrement saisi(s)
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Missions et vigilance -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-stamp">
                    <div class="card-stamp-icon {{ $kpis['overdueMissions'] > 0 ? 'bg-danger' : 'bg-warning' }}">
                        <i class="ti ti-target" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="card-body">
                    <div class="subheader text-secondary">Missions actives</div>
                    <div class="h1 m-0 text-dark">{{ number_format($kpis['activeMissions'], 0, ',', ' ') }}</div>
                    <div class="d-flex align-items-center justify-content-between text-secondary small mt-3 pt-2 border-top">
                        <span>Total : <strong>{{ number_format($kpis['totalMissions'], 0, ',', ' ') }}</strong> mission(s)</span>
                        @if ($kpis['overdueMissions'] > 0)
                            <span class="badge bg-danger text-white">
                                <i class="ti ti-alert-triangle me-1" aria-hidden="true"></i>
                                {{ $kpis['overdueMissions'] }} en retard
                            </span>
                        @else
                            <span class="badge bg-success-lt">0 retard</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- REPARTITIONS : STATUTS & TYPES DE TITRES -->
    <div class="row row-deck row-cards mb-4">
        <!-- Répartition par statut -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-surface py-3">
                    <h3 class="card-title text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-pie-chart text-primary" aria-hidden="true"></i>
                        Répartition par statut du dossier
                    </h3>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        @foreach ($statusDistribution as $item)
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="d-inline-flex align-items-center gap-2">
                                        <span class="badge bg-{{ $item['color'] }}-lt text-{{ $item['color'] }}">
                                            {{ $item['label'] }}
                                        </span>
                                    </span>
                                    <span class="small text-secondary">
                                        <strong class="text-dark">{{ $item['total'] }}</strong> ({{ $item['percentage'] }}%)
                                    </span>
                                </div>
                                <div class="progress progress-sm">
                                    <div
                                        class="progress-bar bg-{{ $item['color'] }}"
                                        style="width: {{ $item['percentage'] }}%"
                                        role="progressbar"
                                        aria-valuenow="{{ $item['percentage'] }}"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-label="{{ $item['label'] }}"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition par type de titre -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-surface py-3">
                    <h3 class="card-title text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-bookmark text-primary" aria-hidden="true"></i>
                        Répartition par type de titre de propriété
                    </h3>
                </div>
                <div class="card-body">
                    @if ($titleTypeDistribution->isEmpty())
                        <div class="text-secondary text-center py-4">Aucune donnée disponible pour les filtres sélectionnés.</div>
                    @else
                        <div class="space-y-3">
                            @foreach ($titleTypeDistribution as $type)
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="d-inline-flex align-items-center gap-2">
                                            <span class="badge bg-primary-lt fw-bold">{{ $type['code'] }}</span>
                                            <span class="fw-medium text-dark">{{ $type['label'] }}</span>
                                        </span>
                                        <span class="small text-secondary">
                                            <strong class="text-dark">{{ $type['total'] }}</strong> ({{ $type['percentage'] }}%)
                                        </span>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar bg-primary"
                                            style="width: {{ $type['percentage'] }}%"
                                            role="progressbar"
                                            aria-valuenow="{{ $type['percentage'] }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            aria-label="{{ $type['label'] }}"
                                        ></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- EVOLUTION MENSUELLE -->
    <div class="card mb-4">
        <div class="card-header bg-surface py-3 d-flex align-items-center justify-content-between">
            <h3 class="card-title text-dark mb-0 d-flex align-items-center gap-2">
                <i class="ti ti-timeline text-primary" aria-hidden="true"></i>
                Évolution mensuelle des recherches et des résolutions
            </h3>
            <span class="badge bg-secondary-lt">
                Sur les {{ request('trend_months', 12) }} derniers mois
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-striped card-table mb-0">
                <thead>
                    <tr>
                        <th>Mois</th>
                        <th class="text-center">Recherches effectuées</th>
                        <th class="text-center">Signalements « Retrouvé »</th>
                        <th class="text-center">Dossiers résolus définitivement</th>
                        <th class="text-end">Taux d'aboutissement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($monthlyTrend as $point)
                        @php
                            $rate = $point['searches'] > 0
                                ? round(($point['foundReports'] / $point['searches']) * 100, 1)
                                : 0.0;
                        @endphp
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $point['label'] }}</strong>
                                <span class="text-muted small ms-1">({{ $point['month'] }})</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-lt text-primary">{{ $point['searches'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-yellow-lt">{{ $point['foundReports'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-lt fw-bold">{{ $point['resolvedForms'] }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <div class="progress progress-xs" style="width: 80px;">
                                        <div
                                            class="progress-bar bg-success"
                                            style="width: {{ min($rate, 100) }}%"
                                            role="progressbar"
                                            aria-valuenow="{{ $rate }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                    <span class="small fw-semibold {{ $rate > 0 ? 'text-success' : 'text-secondary' }}">{{ $rate }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- PERFORMANCES PAR LIEU ET PAR RESPONSABLE -->
    <div class="row row-deck row-cards mb-4">
        <!-- Performance par localisation -->
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header bg-surface py-3">
                    <h3 class="card-title text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-map-pin text-primary" aria-hidden="true"></i>
                        Performance par localisation de recherche
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table mb-0">
                        <thead>
                            <tr>
                                <th>Lieu de recherche</th>
                                <th class="text-center">Recherches</th>
                                <th class="text-center">Retrouvés</th>
                                <th class="text-end">Taux de succès</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($locationPerformance as $loc)
                                <tr>
                                    <td>
                                        <div class="fw-medium text-dark">{{ $loc['label'] }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-lt">{{ $loc['searches'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-lt fw-bold">{{ $loc['found'] }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <div class="progress progress-xs" style="width: 60px;">
                                                <div
                                                    class="progress-bar bg-success"
                                                    style="width: {{ min($loc['successRate'], 100) }}%"
                                                    role="progressbar"
                                                    aria-valuenow="{{ $loc['successRate'] }}"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100"
                                                ></div>
                                            </div>
                                            <span class="small fw-bold text-success">{{ $loc['successRate'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-3">
                                        Aucune recherche enregistrée sur ces critères.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Performance par agent / responsable -->
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header bg-surface py-3">
                    <h3 class="card-title text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-user-check text-primary" aria-hidden="true"></i>
                        Performance par agent / responsable
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table mb-0">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th class="text-center">Recherches</th>
                                <th class="text-center">Retrouvés</th>
                                <th class="text-center">À vérifier</th>
                                <th class="text-end">Taux de succès</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($responsiblePerformance as $resp)
                                <tr>
                                    <td>
                                        <div class="fw-medium text-dark">{{ $resp['label'] }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-lt">{{ $resp['searches'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-lt fw-bold">{{ $resp['found'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning-lt">{{ $resp['needsVerification'] }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <div class="progress progress-xs" style="width: 60px;">
                                                <div
                                                    class="progress-bar bg-success"
                                                    style="width: {{ min($resp['successRate'], 100) }}%"
                                                    role="progressbar"
                                                    aria-valuenow="{{ $resp['successRate'] }}"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100"
                                                ></div>
                                            </div>
                                            <span class="small fw-bold text-success">{{ $resp['successRate'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-3">
                                        Aucune activité d'agent enregistrée sur ces critères.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- REPARTITION PAR ANNEE DE FORMULAIRE -->
    @if ($yearDistribution->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header bg-surface py-3">
                <h3 class="card-title text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-calendar-event text-primary" aria-hidden="true"></i>
                    Volume de dossiers par année de titre
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach ($yearDistribution as $yearItem)
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small">Année {{ $yearItem['year'] }}</div>
                                <div class="h3 mb-0 text-dark">{{ $yearItem['total'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection

