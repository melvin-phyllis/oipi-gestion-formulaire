@extends('layouts.app')

@section('title', 'Notifications et alertes')

@section('content')
    <!-- EN-TETE DE PAGE -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Centre de communication</div>
                <h1 class="page-title d-flex align-items-center gap-2">
                    <span>Notifications & Alertes</span>
                    <span
                        class="badge bg-red text-white fs-6"
                        data-notification-page-count
                        @if ($unreadNotificationsCount === 0) hidden @endif
                    >
                        {{ $unreadNotificationsCount }} non lue{{ $unreadNotificationsCount > 1 ? 's' : '' }}
                    </span>
                </h1>
                <div class="text-secondary mt-1">
                    Suivi des rappels d’échéances, alertes de retards et escalades de missions de recherche.
                </div>
            </div>

                <div
                    class="col-auto ms-auto d-print-none"
                    data-notification-read-all
                    @if ($unreadNotificationsCount === 0) hidden @endif
                >
                    <form method="post" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="ti ti-checks me-1" aria-hidden="true"></i>
                            Tout marquer comme lu
                        </button>
                    </form>
                </div>
        </div>
    </div>

    @php
        $currentStatus = request('status', 'all');
    @endphp

    <div
        class="alert alert-info d-none"
        role="status"
        data-notification-realtime-alert
        data-status-filter="{{ $currentStatus }}"
    >
        Une nouvelle notification est disponible. La liste est actualisée automatiquement si elle correspond au filtre sélectionné.
        <a class="alert-link" href="{{ route('notifications.index', request()->only('status')) }}">Actualiser</a>
    </div>

    <!-- ONGLETS / FILTRES PAR STATUT TABLER -->
    <div class="card">
        <div class="card-header border-bottom-0 pb-0">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $currentStatus === 'all' ? 'active' : '' }}" href="{{ route('notifications.index', ['status' => 'all']) }}">
                        <i class="ti ti-bell me-2 text-primary"></i>
                        Toutes les alertes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $currentStatus === 'unread' ? 'active' : '' }}" href="{{ route('notifications.index', ['status' => 'unread']) }}">
                        <i class="ti ti-bell-ringing me-2 text-warning"></i>
                        Non lues
                        <span
                            class="badge bg-red-lt ms-2"
                            data-notification-tab-count
                            @if ($unreadNotificationsCount === 0) hidden @endif
                        >{{ $unreadNotificationsCount }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $currentStatus === 'read' ? 'active' : '' }}" href="{{ route('notifications.index', ['status' => 'read']) }}">
                        <i class="ti ti-check me-2 text-secondary"></i>
                        Lues
                    </a>
                </li>
            </ul>
        </div>

        <!-- LISTE DES NOTIFICATIONS -->
        <div
            class="list-group list-group-flush"
            data-notifications-list
            data-notification-data-url-template="{{ route('notifications.data', '__notification__') }}"
            data-notification-read-url-template="{{ route('notifications.read', '__notification__') }}"
            data-mission-url-template="{{ route('missions.show', '__uuid__') }}"
            data-form-url-template="{{ route('registry.forms.show', '__uuid__') }}"
        >
            @forelse ($notifications as $notification)
                @php
                    $isUnread = $notification->read_at === null;
                    $data = $notification->data ?? [];
                    $title = $data['title'] ?? 'Notification système';
                    $message = $data['message'] ?? '';
                    $missionUuid = $data['mission_uuid'] ?? null;
                    $missingFormUuid = $data['missing_form_uuid'] ?? null;
                @endphp
                <div class="list-group-item py-3 {{ $isUnread ? 'bg-primary-lt' : '' }}" data-notification-item data-notification-id="{{ $notification->id }}">
                    <div class="row align-items-center g-3">
                        <!-- ICONE NOTIFICATION -->
                        <div class="col-auto">
                            <i class="ti {{ $isUnread ? 'ti-bell-ringing text-red' : 'ti-bell text-secondary' }} fs-1"></i>
                        </div>

                        <!-- CONTENU DE L'ALERTE -->
                        <div class="col">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h3 class="mb-0 fs-4 fw-semibold {{ $isUnread ? 'text-dark' : 'text-secondary' }}">
                                    {{ $title }}
                                </h3>
                                @if ($isUnread)
                                    <span class="badge bg-red text-white">Nouveau</span>
                                @endif
                                <span class="text-secondary small ms-auto">
                                    <i class="ti ti-clock me-1"></i>
                                    {{ $notification->created_at->diffForHumans() }} ({{ $notification->created_at->format('d/m/Y H:i') }})
                                </span>
                            </div>

                            <p class="text-secondary mb-2">
                                {{ $message }}
                            </p>

                            <!-- LIENS D'ACTION CONTEXTUELS -->
                            <div class="d-flex flex-wrap gap-2">
                                @if ($missionUuid)
                                    <a href="{{ route('missions.show', $missionUuid) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-target me-1"></i>
                                        Consulter la mission
                                    </a>
                                @endif

                                @if ($missingFormUuid)
                                    <a href="{{ route('registry.forms.show', $missingFormUuid) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="ti ti-file-text me-1"></i>
                                        Voir le dossier manquant
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- BOUTON MARQUER COMME LU -->
                        @if ($isUnread)
                            <div class="col-auto">
                                <form method="post" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Marquer comme lu">
                                        <i class="ti ti-check me-1"></i>
                                        Marquer lu
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-secondary py-5" data-notification-empty>
                    <div class="empty">
                        <div class="empty-icon text-muted">
                            <i class="ti ti-bell-off fs-1"></i>
                        </div>
                        <p class="empty-title">Aucune notification</p>
                        <p class="empty-subtitle text-secondary">
                            Vous êtes à jour ! Aucune alerte n’est en attente pour le moment.
                        </p>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection

