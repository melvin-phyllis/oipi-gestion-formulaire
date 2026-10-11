<!doctype html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'RISFM') — OIPI</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <div class="page">
        <header class="navbar navbar-expand-md d-print-none bg-white shadow-sm">
            <div class="container-xl">
                <a class="navbar-brand navbar-brand-autodark d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                    <span class="navbar-brand-mark" aria-hidden="true">
                        <i class="ti ti-file-search fs-2"></i>
                    </span>
                    <span>
                        <strong class="d-block text-dark">RISFM</strong>
                        <small class="d-block text-secondary fw-normal">Office Ivoirien de la Propriété Intellectuelle</small>
                    </span>
                </a>
            </div>
        </header>

        <main class="page-wrapper">
            <div class="page-body">
                <div class="container-xl py-4">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
