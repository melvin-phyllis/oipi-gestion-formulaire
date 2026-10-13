@extends('layouts.guest')

@section('title')
    @yield('code', 'Erreur') | @yield('title', 'Erreur')
@endsection

@section('content')
    <div class="card card-md risfm-auth-card">
        <div class="card-body text-center p-4 p-md-5">
            @hasSection('illustration')
                <div class="mb-3">
                    @yield('illustration')
                </div>
            @endif

            <div class="badge bg-secondary-lt text-secondary mb-3 font-monospace">
                ERREUR @yield('code')
            </div>

            <h1 class="h2 mb-2 text-dark">@yield('title')</h1>

            <p class="text-secondary mb-4">
                @yield('message')
            </p>

            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary w-100">
                {{ auth()->check() ? 'Retour au tableau de bord' : 'Retour à la page de connexion' }}
            </a>
        </div>
    </div>
@endsection
