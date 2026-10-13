@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
    <div class="card card-md risfm-auth-card">
        <div class="card-body">
            <h2 class="h2 text-center mb-1">Connexion à votre espace</h2>
            <p class="text-secondary text-center mb-4">Entrez vos identifiants pour accéder à l'application.</p>

            @if (session('status'))
                <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                    <i class="ti ti-circle-check fs-2 me-2" aria-hidden="true"></i>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                    <i class="ti ti-circle-check fs-2 me-2" aria-hidden="true"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                    <i class="ti ti-alert-circle fs-2 me-2" aria-hidden="true"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                    <i class="ti ti-alert-triangle fs-2 me-2" aria-hidden="true"></i>
                    <div>{{ session('warning') }}</div>
                </div>
            @endif

            <form method="post" action="{{ route('login.store') }}" autocomplete="off" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="login">Identifiant ou adresse e-mail</label>
                    <div class="input-group input-group-flat @error('login') is-invalid @enderror">
                        <span class="input-group-text">
                            <i class="ti ti-user text-secondary" aria-hidden="true"></i>
                        </span>
                        <input
                            class="form-control @error('login') is-invalid @enderror"
                            id="login"
                            name="login"
                            type="text"
                            value="{{ old('login') }}"
                            placeholder="ex. nom.prenom ou agent@oipi.ci"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>
                    @error('login')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0" for="signin-password">Mot de passe</label>
                        <a href="{{ route('password.request') }}" class="form-label-description small">
                            Mot de passe oublié ?
                        </a>
                    </div>
                    <div class="input-group input-group-flat @error('password') is-invalid @enderror">
                        <span class="input-group-text">
                            <i class="ti ti-lock text-secondary" aria-hidden="true"></i>
                        </span>
                        <input
                            class="form-control @error('password') is-invalid @enderror"
                            id="signin-password"
                            name="password"
                            type="password"
                            placeholder="Votre mot de passe"
                            autocomplete="current-password"
                            required
                        >
                        <span class="input-group-text">
                            <button
                                type="button"
                                class="input-group-link border-0 bg-transparent p-0"
                                title="Afficher/masquer le mot de passe"
                                aria-label="Afficher le mot de passe"
                                data-password-toggle="signin-password"
                            >
                                <i class="ti ti-eye icon-toggle-show"></i>
                                <i class="ti ti-eye-off icon-toggle-hide d-none"></i>
                            </button>
                        </span>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-check">
                        <input class="form-check-input" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        <span class="form-check-label">Rester connecté sur cet appareil</span>
                    </label>
                </div>

                <div class="form-footer">
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="ti ti-login me-2" aria-hidden="true"></i>
                        Se connecter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center text-secondary mt-3 small">
        <i class="ti ti-shield-check me-1" aria-hidden="true"></i>
        Accès réservé au personnel habilité de l’OIPI. Les accès sont tracés et journalisés.
    </div>
@endsection
