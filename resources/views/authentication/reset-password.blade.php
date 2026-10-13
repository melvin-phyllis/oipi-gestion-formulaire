@extends('layouts.guest')

@section('title', 'Réinitialisation du mot de passe')

@section('content')
    <div class="card card-md risfm-auth-card">
        <div class="card-body">
            <h2 class="h2 text-center mb-1">Nouveau mot de passe</h2>
            <p class="text-secondary text-center mb-4">
                Définissez un mot de passe robuste pour accéder à nouveau à votre compte.
            </p>

            <form method="post" action="{{ route('password.update') }}" autocomplete="off" novalidate>
                @csrf
                <input name="token" type="hidden" value="{{ $token }}">

                <div class="mb-3">
                    <label class="form-label" for="email">Adresse e-mail</label>
                    <div class="input-group input-group-flat @error('email') is-invalid @enderror">
                        <span class="input-group-text">
                            <i class="ti ti-mail text-secondary" aria-hidden="true"></i>
                        </span>
                        <input
                            class="form-control @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $email) }}"
                            autocomplete="email"
                            required
                        >
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Nouveau mot de passe</label>
                    <div class="input-group input-group-flat @error('password') is-invalid @enderror">
                        <span class="input-group-text">
                            <i class="ti ti-lock text-secondary" aria-hidden="true"></i>
                        </span>
                        <input
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Nouveau mot de passe"
                            autocomplete="new-password"
                            required
                        >
                        <span class="input-group-text">
                            <button
                                type="button"
                                class="input-group-link border-0 bg-transparent p-0"
                                title="Afficher/masquer le mot de passe"
                                aria-label="Afficher le mot de passe"
                                data-password-toggle="password"
                            >
                                <i class="ti ti-eye icon-toggle-show"></i>
                                <i class="ti ti-eye-off icon-toggle-hide d-none"></i>
                            </button>
                        </span>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="form-hint small text-secondary mt-1">
                        <i class="ti ti-info-circle me-1" aria-hidden="true"></i>
                        Minimum 12 caractères : majuscules, minuscules, chiffres et symboles.
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <div class="input-group input-group-flat">
                        <span class="input-group-text">
                            <i class="ti ti-shield-check text-secondary" aria-hidden="true"></i>
                        </span>
                        <input
                            class="form-control"
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            placeholder="Confirmation du mot de passe"
                            autocomplete="new-password"
                            required
                        >
                        <span class="input-group-text">
                            <button
                                type="button"
                                class="input-group-link border-0 bg-transparent p-0"
                                title="Afficher/masquer le mot de passe"
                                aria-label="Afficher le mot de passe"
                                data-password-toggle="password_confirmation"
                            >
                                <i class="ti ti-eye icon-toggle-show"></i>
                                <i class="ti ti-eye-off icon-toggle-hide d-none"></i>
                            </button>
                        </span>
                    </div>
                </div>

                <div class="form-footer">
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="ti ti-check me-2" aria-hidden="true"></i>
                        Réinitialiser le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center text-secondary mt-3">
        <a href="{{ route('login') }}" class="text-reset fw-medium">
            <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
            Retour à l'écran de connexion
        </a>
    </div>
@endsection
