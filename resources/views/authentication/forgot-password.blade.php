@extends('layouts.guest')

@section('title', 'Mot de passe oublié')

@section('content')
    <div class="card card-md risfm-auth-card">
        <div class="card-body">
            <h2 class="h2 text-center mb-1">Mot de passe oublié</h2>
            <p class="text-secondary text-center mb-4">
                Saisissez votre adresse e-mail professionnelle. Un lien de réinitialisation sécurisé vous sera envoyé.
            </p>

            @if (session('success'))
                <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                    <i class="ti ti-circle-check fs-2 me-2" aria-hidden="true"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            <form method="post" action="{{ route('password.email') }}" autocomplete="off" novalidate>
                @csrf

                <div class="mb-4">
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
                            value="{{ old('email') }}"
                            placeholder="agent@oipi.ci"
                            autocomplete="email"
                            required
                            autofocus
                        >
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-footer">
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="ti ti-mail-forward me-2" aria-hidden="true"></i>
                        Envoyer le lien de réinitialisation
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
