<!doctype html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">

    <title>@yield('title', 'Connexion') | OIPI</title>
    <link rel="icon" type="image/png" href="{{ asset('images/oipi-favicon.png') }}">

    @include('partials.theme-initializer')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="d-flex flex-column risfm-auth-page">
    <button
        type="button"
        class="btn btn-icon btn-ghost-secondary risfm-theme-toggle risfm-theme-toggle-floating"
        data-theme-toggle
        aria-label="Activer le mode sombre"
        title="Activer le mode sombre"
    >
        <i class="ti ti-moon fs-2 risfm-theme-icon-dark" aria-hidden="true"></i>
        <i class="ti ti-sun fs-2 risfm-theme-icon-light" aria-hidden="true"></i>
        <span class="visually-hidden" data-theme-label>Activer le mode sombre</span>
    </button>

    <main class="page page-center" id="content">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="{{ route('login') }}" class="navbar-brand navbar-brand-autodark d-inline-flex flex-column align-items-center gap-2">
                    <img
                        src="{{ asset('images/oipi-logo.jpg') }}"
                        class="risfm-auth-brand-logo mb-2"
                        alt="Office Ivoirien de la Propriété Intellectuelle"
                    >
                    <span class="text-secondary small fw-medium">Registre informatisé de suivi des formulaires manquants</span>
                </a>
            </div>

            @yield('content')
        </div>
    </main>

    @stack('modals')
    @stack('scripts')
</body>
</html>
