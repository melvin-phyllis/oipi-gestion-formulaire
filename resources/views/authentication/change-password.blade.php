@extends('layouts.guest')

@section('title', 'Modifier votre mot de passe')

@section('content')
    <div class="card card-md risfm-auth-card">
        <div class="card-body">
            <h2 class="h2 text-center mb-1">Sécurisez votre compte</h2>
            <p class="text-secondary text-center mb-4">
                Pour des raisons de sécurité, vous devez définir un nouveau mot de passe .
            </p>

            <form method="post" action="{{ route('password.change.update') }}" autocomplete="off" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="current_password">Mot de passe temporaire actuel</label>
                    <div class="input-group input-group-flat @error('current_password') is-invalid @enderror">
                        <span class="input-group-text">
                            <i class="ti ti-lock text-secondary" aria-hidden="true"></i>
                        </span>
                        <input
                            class="form-control @error('current_password') is-invalid @enderror"
                            id="current_password"
                            name="current_password"
                            type="password"
                            placeholder="Mot de passe temporaire"
                            autocomplete="current-password"
                            required
                            autofocus
                        >
                        <span class="input-group-text">
                            <button
                                type="button"
                                class="input-group-link border-0 bg-transparent p-0"
                                title="Afficher/masquer le mot de passe"
                                aria-label="Afficher le mot de passe"
                                data-password-toggle="current_password"
                            >
                                <i class="ti ti-eye icon-toggle-show"></i>
                                <i class="ti ti-eye-off icon-toggle-hide d-none"></i>
                            </button>
                        </span>
                    </div>
                    @error('current_password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="new_password">Nouveau mot de passe</label>
                    <div class="input-group input-group-flat @error('password') is-invalid @enderror">
                        <span class="input-group-text">
                            <i class="ti ti-key text-secondary" aria-hidden="true"></i>
                        </span>
                        <input
                            class="form-control @error('password') is-invalid @enderror"
                            id="new_password"
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
                                data-password-toggle="new_password"
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
                    <label class="form-label" for="password_confirmation">Confirmer le nouveau mot de passe</label>
                    <div class="input-group input-group-flat">
                        <span class="input-group-text">
                            <i class="ti ti-shield-check text-secondary" aria-hidden="true"></i>
                        </span>
                        <input
                            class="form-control"
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            placeholder="Confirmez le nouveau mot de passe"
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
                        Enregistrer et accéder à RISFM
                    </button>
                </div>
            </form>
        </div>
    </div>

    <form class="text-center mt-3" method="post" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-link text-secondary text-decoration-none small" type="submit">
            <i class="ti ti-logout me-1" aria-hidden="true"></i>
            Se déconnecter
        </button>
    </form>
@endsection
