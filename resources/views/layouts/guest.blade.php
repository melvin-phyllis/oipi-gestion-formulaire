<!doctype html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Connexion') — RISFM OIPI</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="d-flex flex-column risfm-auth-page">
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

    @stack('scripts')
</body>
</html>
