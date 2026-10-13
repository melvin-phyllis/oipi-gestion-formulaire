@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <!-- EN-TETE DE PAGE TABLER -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                
                <h1 class="page-title d-flex align-items-center gap-2">
                    <span>Tableau de bord</span>
                    @if ($dashboardScope === 'personal')
                        <span class="badge border border-primary text-primary bg-transparent fs-6">
                            <i class="ti ti-user me-1"></i>Vue Agent
                        </span>
                    @elseif ($dashboardScope === 'global')
                        <span class="badge border border-success text-success bg-transparent fs-6">
                            <i class="ti ti-world me-1"></i>Vue Globale
                        </span>
                    @else
                        <span class="badge border border-secondary text-secondary bg-transparent fs-6">
                            <i class="ti ti-eye me-1"></i>Consultation
                        </span>
                    @endif
                </h1>
                <div class="text-secondary mt-1">
                    @if ($dashboardScope === 'personal')
                        Suivi de vos missions de recherche assignées et alertes sur vos dossiers opérationnels.
                    @elseif ($dashboardScope === 'global')
                        Pilotage national de la campagne de recherche, suivi des alertes et progression du registre.
                    @else
                        Synthèse générale des formulaires manquants et des indicateurs de la campagne.
                    @endif
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex flex-wrap gap-2">
                @can('analytics_view')
                    <a href="{{ route('analytics.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                        <i class="ti ti-chart-bar" aria-hidden="true"></i>
                        <span>Statistiques & Rapports</span>
                    </a>
                @endcan
                @can('registry_create')
                    <a href="{{ route('registry.forms.create') }}" class="btn btn-primary d-flex align-items-center gap-1">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        <span>Nouveau formulaire manquant</span>
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- INDICATEURS CLES DYNAMIQUES (KPIS TABLER CARD-STAMP) -->
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

        <!-- 3. Taux de saisie ERP -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-stamp">
                    <div class="card-stamp-icon bg-primary">
                        <i class="ti ti-certificate" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="card-body">
                    <div class="subheader text-secondary">Taux de saisie ERP</div>
                    <div class="h1 m-0 text-primary">{{ $kpis['completionRate'] }}%</div>
                    <div class="progress progress-sm mt-3">
                        <div
                            class="progress-bar bg-primary"
                            style="width: {{ min($kpis['completionRate'], 100) }}%"
                            role="progressbar"
                            aria-valuenow="{{ $kpis['completionRate'] }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-label="Taux de saisie ERP"
                        ></div>
                    </div>
                    <div class="text-secondary small mt-2">
                        <strong>{{ number_format($kpis['enteredForms'], 0, ',', ' ') }}</strong> dossier(s) entièrement saisi(s)
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Missions actives et vigilance -->
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
                        <span>Total : <strong>{{ number_format($kpis['totalMissions'], 0, ',', ' ') }}</strong></span>
                        @if ($kpis['overdueMissions'] > 0)
                            <span class="badge bg-danger text-white">
                                <i class="ti ti-alert-triangle me-1" aria-hidden="true"></i>
                                {{ $kpis['overdueMissions'] }} en retard
                            </span>
                        @else
                            <span class="badge bg-success-lt text-success">
                                <i class="ti ti-check me-1"></i>0 en retard
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($dashboardScope !== 'overview')
        <!-- SECTION FILE D'ATTENTION ET ACTIONS PRIORITAIRES -->
        @php
            $hasAnyAttention = array_sum($attentionCounts) > 0;
            $overdueCount = $attentionCounts['overdueMissions'] ?? 0;
            $dueSoonCount = $attentionCounts['dueSoonMissions'] ?? 0;
            $withoutMissionCount = $attentionCounts['formsWithoutMission'] ?? 0;
            $reviewsCount = $attentionCounts['pendingReviews'] ?? 0;
            $finalizationCount = ($attentionCounts['awaitingDigitization'] ?? 0) + ($attentionCounts['awaitingEntry'] ?? 0);
        @endphp

        <div class="card mb-4">
            <div class="card-header border-bottom py-2 px-3 bg-light-subtle d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-bell-ringing text-warning fs-2"></i>
                    <h2 class="card-title mb-0 fs-3 fw-bold">File d'attention opérationnelle</h2>
                </div>
                <ul class="nav nav-tabs card-header-tabs flex-nowrap overflow-auto" id="attention-tabs" role="tablist" style="scrollbar-width: thin;">
                    <!-- ONGLET MISSIONS EN RETARD -->
                    <li class="nav-item" role="presentation">
                        <a
                            href="#tab-overdue"
                            class="nav-link active py-2 px-3 d-flex align-items-center gap-2"
                            data-bs-toggle="tab"
                            role="tab"
                            aria-selected="true"
                        >
                            <i class="ti ti-alert-triangle {{ $overdueCount > 0 ? 'text-danger' : 'text-secondary' }}"></i>
                            <span>Retards</span>
                            <span class="badge {{ $overdueCount > 0 ? 'bg-danger text-white' : 'bg-secondary-lt' }} font-monospace" style="font-size: 0.72rem;">
                                {{ $overdueCount }}
                            </span>
                        </a>
                    </li>

                    <!-- ONGLET ECHEANCES PROCHES -->
                    <li class="nav-item" role="presentation">
                        <a
                            href="#tab-duesoon"
                            class="nav-link py-2 px-3 d-flex align-items-center gap-2"
                            data-bs-toggle="tab"
                            role="tab"
                            aria-selected="false"
                        >
                            <i class="ti ti-clock {{ $dueSoonCount > 0 ? 'text-warning' : 'text-secondary' }}"></i>
                            <span>Échéances proches</span>
                            <span class="badge {{ $dueSoonCount > 0 ? 'bg-warning text-dark' : 'bg-secondary-lt' }} font-monospace" style="font-size: 0.72rem;">
                                {{ $dueSoonCount }}
                            </span>
                        </a>
                    </li>

                    @if ($capabilities['assignMissions'])
                        <!-- ONGLET FORMULAIRES SANS MISSION -->
                        <li class="nav-item" role="presentation">
                            <a
                                href="#tab-without-mission"
                                class="nav-link py-2 px-3 d-flex align-items-center gap-2"
                                data-bs-toggle="tab"
                                role="tab"
                                aria-selected="false"
                            >
                                <i class="ti ti-user-plus {{ $withoutMissionCount > 0 ? 'text-orange' : 'text-secondary' }}"></i>
                                <span>Sans mission</span>
                                <span class="badge {{ $withoutMissionCount > 0 ? 'bg-orange-lt text-orange' : 'bg-secondary-lt' }} font-monospace" style="font-size: 0.72rem;">
                                    {{ $withoutMissionCount }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if ($capabilities['reviewFoundReports'])
                        <!-- ONGLET SIGNALEMENTS A VALIDER -->
                        <li class="nav-item" role="presentation">
                            <a
                                href="#tab-reviews"
                                class="nav-link py-2 px-3 d-flex align-items-center gap-2"
                                data-bs-toggle="tab"
                                role="tab"
                                aria-selected="false"
                            >
                                <i class="ti ti-checklist {{ $reviewsCount > 0 ? 'text-primary' : 'text-secondary' }}"></i>
                                <span>À valider</span>
                                <span class="badge {{ $reviewsCount > 0 ? 'bg-primary-lt text-primary' : 'bg-secondary-lt' }} font-monospace" style="font-size: 0.72rem;">
                                    {{ $reviewsCount }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if ($capabilities['finalizeForms'])
                        <!-- ONGLET FINALISATIONS EN ATTENTE -->
                        <li class="nav-item" role="presentation">
                            <a
                                href="#tab-finalizations"
                                class="nav-link py-2 px-3 d-flex align-items-center gap-2"
                                data-bs-toggle="tab"
                                role="tab"
                                aria-selected="false"
                            >
                                <i class="ti ti-certificate {{ $finalizationCount > 0 ? 'text-green' : 'text-secondary' }}"></i>
                                <span>À finaliser</span>
                                <span class="badge {{ $finalizationCount > 0 ? 'bg-green-lt text-green' : 'bg-secondary-lt' }} font-monospace" style="font-size: 0.72rem;">
                                    {{ $finalizationCount }}
                                </span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="tab-content" id="attention-tab-content">
                <!-- CONTENU 1 : MISSIONS EN RETARD -->
                <div class="tab-pane fade show active" id="tab-overdue" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            <thead>
                                <tr>
                                    <th>Dossier</th>
                                    <th>Titre</th>
                                    <th>Responsable</th>
                                    <th>Localisation</th>
                                    <th>Échéance</th>
                                    <th class="w-1 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attentionItems['overdueMissions'] as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-bold font-monospace text-dark">{{ $item['registryNumber'] }}</div>
                                            <div class="text-secondary small">Formulaire n° {{ $item['formNumber'] }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-lt">{{ $item['titleType'] ?? 'Non précisé' }}</span>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-dark">{{ $item['responsibleName'] ?? 'Non assigné' }}</div>
                                        </td>
                                        <td>
                                            <span class="text-secondary small">{{ $item['searchLocation'] ?? 'Non assigné' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge border border-danger text-danger bg-transparent">
                                                <i class="ti ti-alert-triangle me-1"></i>
                                                {{ $item['dueDate'] ? \Carbon\Carbon::parse($item['dueDate'])->format('d/m/Y') : 'Non définie' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('missions.show', $item['uuid']) }}" class="btn btn-sm btn-outline-danger">
                                                Traiter
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-secondary py-4">
                                            <i class="ti ti-circle-check text-success fs-2 d-block mb-1"></i>
                                            <div>Aucune mission en retard. Toutes les missions sont à jour dans leurs délais.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CONTENU 2 : ECHEANCES PROCHES -->
                <div class="tab-pane fade" id="tab-duesoon" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            <thead>
                                <tr>
                                    <th>Dossier</th>
                                    <th>Titre</th>
                                    <th>Responsable</th>
                                    <th>Localisation</th>
                                    <th>Échéance</th>
                                    <th class="w-1 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attentionItems['dueSoonMissions'] as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-bold font-monospace text-dark">{{ $item['registryNumber'] }}</div>
                                            <div class="text-secondary small">Formulaire n° {{ $item['formNumber'] }}</div>
                                        </td>
                                        <td>
                                            <span class="badge border text-secondary bg-transparent">{{ $item['titleType'] ?? 'Non précisé' }}</span>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-dark">{{ $item['responsibleName'] ?? 'Non assigné' }}</div>
                                        </td>
                                        <td>
                                            <span class="text-secondary small">{{ $item['searchLocation'] ?? 'Non assigné' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge border border-warning text-warning bg-transparent">
                                                <i class="ti ti-clock me-1"></i>
                                                {{ $item['dueDate'] ? \Carbon\Carbon::parse($item['dueDate'])->format('d/m/Y') : 'Non définie' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('missions.show', $item['uuid']) }}" class="btn btn-sm btn-outline-warning">
                                                Consulter
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-secondary py-4">
                                            <i class="ti ti-circle-check text-success fs-2 d-block mb-1"></i>
                                            <div>Aucune échéance urgente à court terme (≤ 7 jours).</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($capabilities['assignMissions'])
                    <!-- CONTENU 3 : FORMULAIRES SANS MISSION -->
                    <div class="tab-pane fade" id="tab-without-mission" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Dossier</th>
                                        <th>Titre & Année</th>
                                        <th>Priorité</th>
                                        <th>Statut</th>
                                        <th class="w-1 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($attentionItems['formsWithoutMission'] as $form)
                                        <tr>
                                            <td>
                                                <div class="fw-bold font-monospace text-dark">{{ $form['registryNumber'] }}</div>
                                                <div class="text-secondary small">Formulaire n° {{ $form['formNumber'] }}</div>
                                            </td>
                                            <td>
                                                <div>{{ $form['titleType'] ?? 'Non précisé' }}</div>
                                                <div class="text-secondary small">Année {{ $form['formYear'] }}</div>
                                            </td>
                                            <td>
                                                @if ($form['priority'] === 'urgent')
                                                    <span class="badge bg-danger text-white">{{ $form['priorityLabel'] }}</span>
                                                @elseif ($form['priority'] === 'high')
                                                    <span class="badge bg-orange-lt text-orange">{{ $form['priorityLabel'] }}</span>
                                                @else
                                                    <span class="badge bg-secondary-lt">{{ $form['priorityLabel'] }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-lt">{{ $form['statusLabel'] }}</span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('registry.forms.show', $form['uuid']) }}" class="btn btn-sm btn-primary">
                                                    <i class="ti ti-user-plus me-1"></i>Affecter
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-secondary py-4">
                                                <i class="ti ti-circle-check text-success fs-2 d-block mb-1"></i>
                                                <div>Tous les formulaires manquants sont actuellement pris en charge par une mission active.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($capabilities['reviewFoundReports'])
                    <!-- CONTENU 4 : SIGNALEMENTS A VALIDER -->
                    <div class="tab-pane fade" id="tab-reviews" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Dossier</th>
                                        <th>Titre & Année</th>
                                        <th>Dernier responsable</th>
                                        <th>Lieu de recherche</th>
                                        <th class="w-1 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($attentionItems['pendingReviews'] as $form)
                                        <tr>
                                            <td>
                                                <div class="fw-bold font-monospace text-dark">{{ $form['registryNumber'] }}</div>
                                                <div class="text-secondary small">Formulaire n° {{ $form['formNumber'] }}</div>
                                            </td>
                                            <td>
                                                <div>{{ $form['titleType'] ?? 'Non précisé' }}</div>
                                                <div class="text-secondary small">Année {{ $form['formYear'] }}</div>
                                            </td>
                                            <td>
                                                <span class="small fw-semibold">{{ $form['responsibleName'] ?? 'Non assigné' }}</span>
                                            </td>
                                            <td>
                                                <span class="text-secondary small">{{ $form['searchLocation'] ?? 'Non assigné' }}</span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('registry.forms.show', $form['uuid']) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-checklist me-1"></i>Examiner
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-secondary py-4">
                                                <i class="ti ti-circle-check text-success fs-2 d-block mb-1"></i>
                                                <div>Aucun signalement de formulaire retrouvé en attente d'examen.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($capabilities['finalizeForms'])
                    <!-- CONTENU 5 : FINALISATIONS EN ATTENTE -->
                    <div class="tab-pane fade" id="tab-finalizations" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Dossier</th>
                                        <th>Titre & Année</th>
                                        <th>Étape attendue</th>
                                        <th>Priorité</th>
                                        <th class="w-1 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $finalizationItems = $attentionItems['awaitingDigitization']->merge($attentionItems['awaitingEntry']);
                                    @endphp
                                    @forelse ($finalizationItems as $form)
                                        <tr>
                                            <td>
                                                <div class="fw-bold font-monospace text-dark">{{ $form['registryNumber'] }}</div>
                                                <div class="text-secondary small">Formulaire n° {{ $form['formNumber'] }}</div>
                                            </td>
                                            <td>
                                                <div>{{ $form['titleType'] ?? 'Non précisé' }}</div>
                                                <div class="text-secondary small">Année {{ $form['formYear'] }}</div>
                                            </td>
                                            <td>
                                                @if ($form['status'] === 'found')
                                                    <span class="badge border border-warning text-warning bg-transparent">
                                                        <i class="ti ti-scan me-1"></i>À numériser
                                                    </span>
                                                @elseif ($form['status'] === 'digitized')
                                                    <span class="badge border border-primary text-primary bg-transparent">
                                                        <i class="ti ti-device-desktop me-1"></i>À saisir ERP
                                                    </span>
                                                @else
                                                    <span class="badge border text-secondary bg-transparent">{{ $form['statusLabel'] }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($form['priority'] === 'urgent')
                                                    <span class="badge bg-danger text-white">{{ $form['priorityLabel'] }}</span>
                                                @elseif ($form['priority'] === 'high')
                                                    <span class="badge bg-orange-lt text-orange">{{ $form['priorityLabel'] }}</span>
                                                @else
                                                    <span class="badge bg-secondary-lt">{{ $form['priorityLabel'] }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('registry.forms.show', $form['uuid']) }}" class="btn btn-sm btn-outline-success">
                                                    Finaliser
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-secondary py-4">
                                                <i class="ti ti-circle-check text-success fs-2 d-block mb-1"></i>
                                                <div>Aucun dossier en attente de numérisation ou de saisie finale dans l'ERP.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- PROGRESSION DU WORKFLOW & TENDANCE MENSUELLE -->
    <div class="row row-cards mb-4">
        <!-- COLONNE GAUCHE : PROGRESSION DU CYCLE DE VIE -->
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header py-3 bg-light-subtle d-flex justify-content-between align-items-center">
                    <h3 class="card-title d-flex align-items-center gap-2 mb-0">
                        <i class="ti ti-git-fork text-primary fs-2"></i>
                        <span>Progression du cycle de vie du registre</span>
                    </h3>
                    <span class="badge bg-secondary-lt font-monospace text-secondary">
                        {{ number_format($kpis['activeCampaignForms'], 0, ',', ' ') }} en campagne
                    </span>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-3">
                        @foreach ($workflowProgress as $step)
                            @php
                                $badgeColor = match ($step['color']) {
                                    'danger' => 'bg-danger text-white',
                                    'warning' => 'bg-warning text-dark',
                                    'info', 'primary', 'azure', 'teal' => 'bg-primary text-white',
                                    'success' => 'bg-success text-white',
                                    default => 'bg-secondary text-white',
                                };
                                $barBg = match ($step['color']) {
                                    'danger' => 'bg-danger',
                                    'warning' => 'bg-warning',
                                    'info', 'primary', 'azure', 'teal' => 'bg-primary',
                                    'success' => 'bg-success',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge {{ $badgeColor }} rounded-circle p-1" style="width: 1.5rem; height: 1.5rem; display: inline-flex; align-items: center; justify-content: center; font-size: 0.72rem;">
                                            {{ $step['order'] }}
                                        </span>
                                        <span class="fw-semibold text-dark">{{ $step['label'] }}</span>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold text-dark font-monospace">{{ number_format($step['total'], 0, ',', ' ') }}</span>
                                        <span class="text-secondary small ms-1">({{ $step['percentage'] }}%)</span>
                                    </div>
                                </div>
                                <div class="progress progress-sm">
                                    <div
                                        class="progress-bar {{ $barBg }}"
                                        style="width: {{ min($step['percentage'], 100) }}%"
                                        role="progressbar"
                                        aria-valuenow="{{ $step['percentage'] }}"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-label="{{ $step['label'] }}"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE : EVOLUTION SUR 6 MOIS -->
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header py-3 bg-light-subtle d-flex justify-content-between align-items-center">
                    <h3 class="card-title d-flex align-items-center gap-2 mb-0">
                        <i class="ti ti-chart-line text-primary fs-2"></i>
                        <span>Évolution mensuelle sur six mois</span>
                    </h3>
                    @if ($monthlyTrend->isNotEmpty())
                        <ul class="nav nav-pills card-header-pills" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link active py-1 px-2 small"
                                    id="trend-chart-tab"
                                    data-bs-toggle="pill"
                                    data-bs-target="#trend-chart-pane"
                                    type="button"
                                    role="tab"
                                    aria-controls="trend-chart-pane"
                                    aria-selected="true"
                                >
                                    <i class="ti ti-chart-area-line me-1" aria-hidden="true"></i>Graphique
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link py-1 px-2 small"
                                    id="trend-table-tab"
                                    data-bs-toggle="pill"
                                    data-bs-target="#trend-table-pane"
                                    type="button"
                                    role="tab"
                                    aria-controls="trend-table-pane"
                                    aria-selected="false"
                                >
                                    <i class="ti ti-table me-1" aria-hidden="true"></i>Tableau
                                </button>
                            </li>
                        </ul>
                    @endif
                </div>

                @if ($monthlyTrend->isEmpty())
                    <div class="card-body text-center text-secondary py-5">
                        <i class="ti ti-chart-line-off fs-1 text-muted d-block mb-2"></i>
                        <div class="fw-semibold">Aucune tendance disponible pour cette période.</div>
                        <div class="small text-secondary">Les statistiques évolutives s'afficheront dès que des formulaires seront enregistrés.</div>
                    </div>
                @else
                    <div class="tab-content">
                        <!-- VUE GRAPHIQUE TABLER / APEXCHARTS -->
                        <div class="tab-pane fade show active p-3" id="trend-chart-pane" role="tabpanel" aria-labelledby="trend-chart-tab">
                            <div id="chart-monthly-trend" style="min-height: 280px; width: 100%;"></div>
                        </div>

                        <!-- VUE TABLEAU RECAPITULATIF -->
                        <div class="tab-pane fade" id="trend-table-pane" role="tabpanel" aria-labelledby="trend-table-tab">
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Mois</th>
                                            <th class="text-center">Déclarés</th>
                                            <th class="text-center">Recherches</th>
                                            <th class="text-center">Retrouvés</th>
                                            <th class="text-center">Résolus</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($monthlyTrend as $trend)
                                            <tr>
                                                <td class="fw-semibold text-dark">{{ $trend['label'] }}</td>
                                                <td class="text-center font-monospace text-secondary">
                                                    {{ $trend['registrations'] }}
                                                </td>
                                                <td class="text-center font-monospace text-secondary">
                                                    {{ $trend['searches'] }}
                                                </td>
                                                <td class="text-center font-monospace text-dark">
                                                    {{ $trend['foundReports'] }}
                                                </td>
                                                <td class="text-center font-monospace text-primary fw-bold">
                                                    {{ $trend['resolvedForms'] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- RECAPITULATIF GLOBAL DES SIX MOIS -->
                    <div class="card-footer bg-light-subtle py-2 px-3 border-top">
                        <div class="row row-cols-2 row-cols-sm-4 text-center g-2">
                            <div class="col">
                                <div class="text-secondary small">Déclarés</div>
                                <div class="fw-bold font-monospace text-dark fs-4">{{ $monthlyTrend->sum('registrations') }}</div>
                            </div>
                            <div class="col">
                                <div class="text-secondary small">Recherches</div>
                                <div class="fw-bold font-monospace text-dark fs-4">{{ $monthlyTrend->sum('searches') }}</div>
                            </div>
                            <div class="col">
                                <div class="text-secondary small">Retrouvés</div>
                                <div class="fw-bold font-monospace text-dark fs-4">{{ $monthlyTrend->sum('foundReports') }}</div>
                            </div>
                            <div class="col">
                                <div class="text-secondary small">Résolus</div>
                                <div class="fw-bold font-monospace text-primary fs-4">{{ $monthlyTrend->sum('resolvedForms') }}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- DOSSIERS PRIORITAIRES & JOURNAL D'ACTIVITE RECENTS -->
    <div class="row row-cards mb-4">
        @if ($dashboardScope !== 'overview')
            <!-- DOSSIERS PRIORITAIRES -->
            <div class="col-12 {{ $capabilities['viewOperationalActivity'] ? 'col-xl-7' : 'col-12' }}">
                <div class="card h-100">
                    <div class="card-header py-3 bg-light-subtle d-flex justify-content-between align-items-center">
                        <h3 class="card-title d-flex align-items-center gap-2 mb-0">
                            <i class="ti ti-flame text-danger fs-2"></i>
                            <span>Dossiers urgents et haute priorité</span>
                        </h3>
                        <span class="badge bg-danger-lt text-danger font-monospace">
                            {{ $priorityForms->count() }} dossier(s)
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            <thead>
                                <tr>
                                    <th>Dossier</th>
                                    <th>Titre & Année</th>
                                    <th>Priorité</th>
                                    <th>Statut</th>
                                    <th class="w-1 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($priorityForms as $form)
                                    <tr>
                                        <td>
                                            <div class="fw-bold font-monospace text-dark">{{ $form['registryNumber'] }}</div>
                                            <div class="text-secondary small">Formulaire n° {{ $form['formNumber'] }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $form['titleType'] ?? 'Non précisé' }}</div>
                                            <div class="text-secondary small">Année {{ $form['formYear'] }}</div>
                                        </td>
                                        <td>
                                            @if ($form['priority'] === 'urgent')
                                                <span class="badge border border-danger text-danger bg-transparent">
                                                    <i class="ti ti-flame me-1"></i>{{ $form['priorityLabel'] }}
                                                </span>
                                            @elseif ($form['priority'] === 'high')
                                                <span class="badge border border-orange text-orange bg-transparent">
                                                    {{ $form['priorityLabel'] }}
                                                </span>
                                            @else
                                                <span class="badge border text-secondary bg-transparent">
                                                    {{ $form['priorityLabel'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge border text-secondary bg-transparent">{{ $form['statusLabel'] }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('registry.forms.show', $form['uuid']) }}" class="btn btn-sm btn-outline-primary">
                                                Consulter
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-4">
                                            <div class="fw-semibold">Aucun dossier urgent en attente.</div>
                                            <div class="small text-secondary">Tous les dossiers prioritaires de votre périmètre sont traités.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if ($capabilities['viewOperationalActivity'])
            <!-- ACTIVITES RECENTES DU JOURNAL -->
            <div class="col-12 {{ $dashboardScope !== 'overview' ? 'col-xl-5' : 'col-12' }}">
                <div class="card h-100">
                    <div class="card-header py-3 bg-light-subtle d-flex justify-content-between align-items-center">
                        <h3 class="card-title d-flex align-items-center gap-2 mb-0">
                            <i class="ti ti-history text-primary fs-2"></i>
                            <span>Activités récentes</span>
                        </h3>
                        @can('audit_view')
                            <a href="{{ route('audit.index') }}" class="btn btn-sm btn-outline-secondary">
                                Tout voir
                            </a>
                        @endcan
                    </div>
                    <div class="card-body p-3">
                        <div class="divide-y">
                            @forelse ($recentActivity as $activity)
                                <div class="py-2">
                                    <div class="row align-items-center">
                                        
                                        <div class="col">
                                            <div class="text-truncate">
                                                <strong>{{ $activity['actorName'] ?? 'Système' }}</strong> :
                                                <span class="text-dark">{{ $activity['actionLabel'] }}</span>
                                            </div>
                                            <div class="text-secondary small text-truncate">
                                                {{ $activity['description'] }}
                                            </div>
                                        </div>
                                        <div class="col-auto text-secondary small">
                                            {{ $activity['occurredAt'] ? \Carbon\Carbon::parse($activity['occurredAt'])->diffForHumans() : '' }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-secondary py-4">
                                    <i class="ti ti-history-off fs-1 text-muted d-block mb-1"></i>
                                    <div>Aucune activité récente enregistrée.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@if ($monthlyTrend->isNotEmpty())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof window.ApexCharts === 'undefined') {
                    return;
                }

                const chartContainer = document.getElementById('chart-monthly-trend');
                if (!chartContainer) {
                    return;
                }

                const categories = @json($monthlyTrend->pluck('label')->values());
                const registrationsData = @json($monthlyTrend->pluck('registrations')->values());
                const searchesData = @json($monthlyTrend->pluck('searches')->values());
                const foundReportsData = @json($monthlyTrend->pluck('foundReports')->values());
                const resolvedFormsData = @json($monthlyTrend->pluck('resolvedForms')->values());

                const options = {
                    series: [
                        {
                            name: 'Déclarés',
                            data: registrationsData,
                        },
                        {
                            name: 'Recherches',
                            data: searchesData,
                        },
                        {
                            name: 'Retrouvés',
                            data: foundReportsData,
                        },
                        {
                            name: 'Résolus',
                            data: resolvedFormsData,
                        },
                    ],
                    chart: {
                        type: 'line',
                        height: 290,
                        fontFamily: 'inherit',
                        parentHeightOffset: 0,
                        toolbar: {
                            show: false,
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 500,
                        },
                    },
                    colors: [
                        '#adb5bd',
                        '#6c757d',
                        '#009846',
                        '#009846',
                    ],
                    dataLabels: {
                        enabled: false,
                    },
                    fill: {
                        opacity: 0,
                    },
                    stroke: {
                        curve: 'smooth',
                        width: [2, 2, 2, 3],
                        dashArray: [6, 3, 6, 0],
                        lineCap: 'round',
                    },
                    grid: {
                        strokeDashArray: 4,
                        padding: {
                            top: -10,
                            right: 12,
                            left: 12,
                            bottom: 0,
                        },
                    },
                    xaxis: {
                        categories: categories,
                        labels: {
                            padding: 4,
                            style: {
                                fontSize: '12px',
                                fontWeight: 500,
                            },
                        },
                        axisBorder: {
                            show: false,
                        },
                        axisTicks: {
                            show: false,
                        },
                        tooltip: {
                            enabled: false,
                        },
                    },
                    yaxis: {
                        labels: {
                            padding: 4,
                            formatter: function (val) {
                                return Math.round(val);
                            },
                        },
                        min: 0,
                        forceNiceScale: true,
                    },
                    legend: {
                        show: true,
                        position: 'top',
                        horizontalAlign: 'right',
                        markers: {
                            radius: 12,
                            width: 10,
                            height: 10,
                        },
                        itemMargin: {
                            horizontal: 10,
                            vertical: 4,
                        },
                    },
                    tooltip: {
                        theme: 'light',
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: function (val) {
                                return val + ' dossier(s)';
                            },
                        },
                    },
                    markers: {
                        size: 0,
                        strokeWidth: 2,
                        hover: {
                            size: 4,
                        },
                    },
                };

                const chart = new window.ApexCharts(chartContainer, options);
                chart.render();

                // Recalcul de taille fluide lors de l'activation de l'onglet
                const chartTab = document.getElementById('trend-chart-tab');
                if (chartTab) {
                    chartTab.addEventListener('shown.bs.tab', function () {
                        window.dispatchEvent(new Event('resize'));
                    });
                }
            });
        </script>
    @endpush
@endif
