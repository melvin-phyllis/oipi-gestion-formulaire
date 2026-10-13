<!doctype html>
<html lang="fr" data-bs-theme="light" data-bs-navbar-position="vertical">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'RISFM') — OIPI</title>

    <script>
        (() => {
            try {
                if (localStorage.getItem('tabler-sidebar') === 'folded-hover') {
                    document.documentElement.setAttribute('data-bs-sidebar', 'folded-hover');
                }
            } catch (error) {
                // Le stockage local peut être indisponible en navigation privée stricte.
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <div class="page">
        <!-- SIDEBAR / NAVBAR VERTICALE TABLER -->
        <aside class="navbar navbar-vertical navbar-expand-lg d-print-none bg-white border-end" id="sidebar">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Afficher le menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="risfm-sidebar-header d-flex align-items-center">
                    <h1 class="navbar-brand navbar-brand-autodark mb-0">
                        <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none text-reset">
                            <img
                                src="{{ asset('images/oipi-logo.jpg') }}"
                                class="risfm-sidebar-brand-logo"
                                alt="Office Ivoirien de la Propriété Intellectuelle"
                            >
                        </a>
                    </h1>
                    <button type="button" class="risfm-sidebar-toggle btn btn-action btn-icon d-none d-lg-inline-flex" data-bs-toggle="sidebar-folded" data-risfm-sidebar-toggle aria-label="Réduire la barre latérale" aria-pressed="false" title="Réduire la barre latérale">
                        <i class="risfm-sidebar-icon-collapse ti ti-layout-sidebar-left-collapse fs-2" aria-hidden="true"></i>
                        <i class="risfm-sidebar-icon-expand ti ti-layout-sidebar-left-expand fs-2" aria-hidden="true"></i>
                    </button>
                </div>

                @auth
                    <!-- ACTIONS MOBILE (EN HAUT A DROITE SUR ECRAN MOBILE) -->
                    <div class="navbar-nav flex-row order-lg-last d-flex d-lg-none ms-auto align-items-center">
                        @can('notifications_view')
                            <div class="nav-item me-2">
                                <a
                                    href="{{ route('notifications.index') }}"
                                    class="nav-link px-2 position-relative {{ request()->routeIs('notifications.*') ? 'active text-primary' : 'text-secondary' }}"
                                    aria-label="Notifications"
                                    title="Notifications"
                                    data-user-notifications-realtime
                                    data-user-uuid="{{ auth()->user()->uuid }}"
                                    data-unread-count="{{ $unreadNotificationsCount ?? 0 }}"
                                >
                                    <i class="ti ti-bell fs-2 {{ ($unreadNotificationsCount ?? 0) > 0 ? 'risfm-bell-shake' : '' }}" data-notification-bell aria-hidden="true"></i>
                                    <span
                                        class="badge bg-red text-white badge-pill risfm-notification-badge"
                                        data-notification-badge
                                        aria-label="{{ $unreadNotificationsCount ?? 0 }} notifications non lues"
                                        @if (($unreadNotificationsCount ?? 0) === 0) hidden @endif
                                    >
                                        {{ ($unreadNotificationsCount ?? 0) > 99 ? '99+' : ($unreadNotificationsCount ?? 0) }}
                                    </span>
                                    <span class="visually-hidden" data-notification-live aria-live="polite"></span>
                                </a>
                            </div>
                        @endcan

                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Menu utilisateur" aria-expanded="false">
                                <span class="avatar avatar-sm bg-primary-lt text-primary fw-bold">
                                    {{ mb_strtoupper(mb_substr(auth()->user()->first_names, 0, 1) . mb_substr(auth()->user()->last_name, 0, 1)) }}
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                <div class="dropdown-header">Compte connecté</div>
                                <div class="dropdown-item-text text-secondary small py-1">
                                    {{ auth()->user()->email }}
                                </div>
                                <div class="dropdown-divider"></div>
                                @can('profile_update')
                                    <a class="dropdown-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ Route::has('profile.edit') ? route('profile.edit') : url('profile') }}">
                                        <i class="ti ti-user me-2 dropdown-item-icon" aria-hidden="true"></i>
                                        Mon profil
                                    </a>
                                @endcan
                                <a class="dropdown-item" href="{{ route('password.change') }}">
                                    <i class="ti ti-key me-2 dropdown-item-icon" aria-hidden="true"></i>
                                    Changer de mot de passe
                                </a>
                                <div class="dropdown-divider"></div>
                                <form method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit">
                                        <i class="ti ti-logout me-2 dropdown-item-icon text-danger" aria-hidden="true"></i>
                                        Déconnexion
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endauth

                <!-- MENU PRINCIPAL -->
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        <li class="nav-section-title">Navigation</li>

                        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('dashboard') }}">
                                <span class="nav-link-icon">
                                    <i class="ti ti-home"></i>
                                </span>
                                <span class="nav-link-title">Tableau de bord</span>
                            </a>
                        </li>

                        @can('registry_view')
                            <li class="nav-item {{ request()->routeIs('registry.forms.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('registry.forms.index') }}">
                                    <span class="nav-link-icon">
                                        <i class="ti ti-file-search"></i>
                                    </span>
                                    <span class="nav-link-title">Registre</span>
                                </a>
                            </li>
                        @endcan

                        @canany(['missions_assign', 'missions_record_any_result', 'missions_record_own_result', 'missions_review_found'])
                            <li class="nav-item {{ request()->routeIs('missions.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('missions.index') }}">
                                    <span class="nav-link-icon">
                                        <i class="ti ti-target"></i>
                                    </span>
                                    <span class="nav-link-title">Missions</span>
                                </a>
                            </li>
                        @endcanany


                            @can('analytics_view')
                                <li class="nav-item {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('analytics.index') }}">
                                        <span class="nav-link-icon">
                                            <i class="ti ti-chart-bar"></i>
                                        </span>
                                        <span class="nav-link-title">Statistiques</span>
                                    </a>
                                </li>
                            @endcan

                            @can('audit_view')
                                <li class="nav-item {{ request()->routeIs('audit.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('audit.index') }}">
                                        <span class="nav-link-icon">
                                            <i class="ti ti-history"></i>
                                        </span>
                                        <span class="nav-link-title">Journal d’audit</span>
                                    </a>
                                </li>
                            @endcan

                            @canany(['users_manage', 'settings_manage', 'backups_manage'])
                                <li class="nav-section-title">Administration</li>
                            @endcanany

                            @can('users_manage')
                                <li class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('users.index') }}">
                                        <span class="nav-link-icon">
                                            <i class="ti ti-users"></i>
                                        </span>
                                        <span class="nav-link-title">Utilisateurs</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('roles.index') }}">
                                        <span class="nav-link-icon">
                                            <i class="ti ti-shield-lock"></i>
                                        </span>
                                        <span class="nav-link-title">Rôles & Permissions</span>
                                    </a>
                                </li>
                            @endcan

                            @can('settings_manage')
                                <li class="nav-item dropdown {{ request()->routeIs('organization.*', 'registry.settings.*') ? 'active' : '' }}">
                                    <a class="nav-link dropdown-toggle" href="#navbar-settings" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ request()->routeIs('organization.*', 'registry.settings.*') ? 'true' : 'false' }}">
                                        <span class="nav-link-icon">
                                            <i class="ti ti-settings"></i>
                                        </span>
                                        <span class="nav-link-title">Paramètres</span>
                                    </a>
                                    <div class="dropdown-menu {{ request()->routeIs('organization.*', 'registry.settings.*') ? 'show' : '' }}">
                                        <a class="dropdown-item {{ request()->routeIs('organization.*') ? 'active' : '' }}" href="{{ route('organization.index') }}">
                                            <i class="ti ti-building me-2 dropdown-item-icon" aria-hidden="true"></i>
                                            Organisation (Directions & Services)
                                        </a>
                                        <a class="dropdown-item {{ request()->routeIs('registry.settings.*') ? 'active' : '' }}" href="{{ route('registry.settings.index') }}">
                                            <i class="ti ti-list-check me-2 dropdown-item-icon" aria-hidden="true"></i>
                                            Listes métier du registre
                                        </a>
                                    </div>
                                </li>
                            @endcan

                            @can('backups_manage')
                                <li class="nav-item {{ request()->routeIs('backups.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('backups.index') }}">
                                        <span class="nav-link-icon">
                                            <i class="ti ti-database"></i>
                                        </span>
                                        <span class="nav-link-title">Sauvegardes</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </div>
            </div>
        </aside>

        <!-- HEADER / TOPBAR TABLER EN HAUT (DESKTOP) -->
        <header class="navbar navbar-expand-md d-none d-lg-flex d-print-none bg-white border-bottom">
            <div class="container-xl">
                <div class="navbar-nav flex-row order-md-last ms-auto align-items-center">
                    @auth
                        @can('notifications_view')
                            <div class="nav-item me-3">
                                <a
                                    href="{{ route('notifications.index') }}"
                                    class="nav-link px-2 position-relative {{ request()->routeIs('notifications.*') ? 'active text-primary' : 'text-secondary' }}"
                                    aria-label="Notifications"
                                    title="Notifications"
                                    data-user-notifications-realtime
                                    data-user-uuid="{{ auth()->user()->uuid }}"
                                    data-unread-count="{{ $unreadNotificationsCount ?? 0 }}"
                                >
                                    <i class="ti ti-bell fs-2 {{ ($unreadNotificationsCount ?? 0) > 0 ? 'risfm-bell-shake' : '' }}" data-notification-bell aria-hidden="true"></i>
                                    <span
                                        class="badge bg-red text-white badge-pill risfm-notification-badge"
                                        data-notification-badge
                                        aria-label="{{ $unreadNotificationsCount ?? 0 }} notifications non lues"
                                        @if (($unreadNotificationsCount ?? 0) === 0) hidden @endif
                                    >
                                        {{ ($unreadNotificationsCount ?? 0) > 99 ? '99+' : ($unreadNotificationsCount ?? 0) }}
                                    </span>
                                    <span class="visually-hidden" data-notification-live aria-live="polite"></span>
                                </a>
                            </div>
                        @endcan

                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Menu utilisateur" aria-expanded="false">
                                <span class="avatar avatar-sm bg-primary-lt text-primary fw-bold me-2">
                                    {{ mb_strtoupper(mb_substr(auth()->user()->first_names, 0, 1) . mb_substr(auth()->user()->last_name, 0, 1)) }}
                                </span>
                                <div class="d-none d-xl-block text-start">
                                    <div class="fw-medium text-truncate" style="max-width: 150px;">
                                        {{ auth()->user()->first_names }} {{ auth()->user()->last_name }}
                                    </div>
                                    <div class="small text-secondary">{{ auth()->user()->role?->role_name ?? 'Agent' }}</div>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                <div class="dropdown-header">Compte connecté</div>
                                <div class="dropdown-item-text text-secondary small py-1">
                                    {{ auth()->user()->email }}
                                </div>
                                <div class="dropdown-divider"></div>
                                @can('profile_update')
                                    <a class="dropdown-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ Route::has('profile.edit') ? route('profile.edit') : url('profile') }}">
                                        <i class="ti ti-user me-2 dropdown-item-icon" aria-hidden="true"></i>
                                        Mon profil
                                    </a>
                                @endcan

                                <div class="dropdown-divider"></div>
                                <form method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit">
                                        <i class="ti ti-logout me-2 dropdown-item-icon text-danger" aria-hidden="true"></i>
                                        Déconnexion
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </header>

        <!-- PAGE WRAPPER -->
        <div class="page-wrapper">
            <main class="page-body">
                <div class="container-xl py-3">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible d-flex align-items-center mb-3" role="alert">
                            <i class="ti ti-circle-check fs-2 me-2" aria-hidden="true"></i>
                            <div class="flex-fill">{{ session('success') }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible d-flex align-items-center mb-3" role="alert">
                            <i class="ti ti-info-circle fs-2 me-2" aria-hidden="true"></i>
                            <div class="flex-fill">{{ session('status') }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible d-flex align-items-center mb-3" role="alert">
                            <i class="ti ti-alert-circle fs-2 me-2" aria-hidden="true"></i>
                            <div class="flex-fill">{{ $errors->first() }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('modals')
    @stack('scripts')
</body>
</html>
