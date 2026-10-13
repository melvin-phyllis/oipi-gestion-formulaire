@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
    @php
        $user = $user ?? auth()->user();
    @endphp

    <!-- EN-TETE DE PAGE -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <h1 class="page-title d-flex align-items-center gap-2">
                    <span>Mon profil utilisateur</span>
                    <span class="badge bg-primary-lt text-primary fs-6 font-monospace">
                        {{ $user->user_code_unique }}
                    </span>
                </h1>
                <div class="text-secondary mt-1">
                    Consultez les informations de votre compte et mettez à jour vos coordonnées personnelles.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">
                    <i class="ti ti-arrow-left me-1"></i>Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- COLONNE GAUCHE : RECAPITULATIF DU COMPTE ET SECURITE -->
        <div class="col-12 col-xl-4">
            <!-- CARTE RECAPITULATIF UTILISATEUR -->
            <div class="card mb-3">
                <div class="card-body text-center py-4">
                    <span class="avatar avatar-xl bg-primary-lt text-primary fw-bold mb-3 fs-1">
                        {{ mb_strtoupper(mb_substr($user->first_names, 0, 1) . mb_substr($user->last_name, 0, 1)) }}
                    </span>
                    <h3 class="card-title fs-2 mb-1">{{ $user->first_names }} {{ $user->last_name }}</h3>
                    <div class="text-secondary small mb-2 font-monospace">{{ $user->email }}</div>
                    <div>
                        <span class="badge bg-primary text-white">
                            {{ $user->role?->role_name ?? 'Agent' }}
                        </span>
                        @if ($user->is_active)
                            <span class="badge bg-green-lt text-green ms-1">
                                <span class="badge badge-dot bg-green me-1"></span>Actif
                            </span>
                        @else
                            <span class="badge bg-secondary-lt text-secondary ms-1">Inactif</span>
                        @endif
                    </div>
                </div>

                <div class="card-body border-top py-3">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-secondary">Identifiant</dt>
                        <dd class="col-7 font-monospace fw-bold text-dark mb-2">{{ $user->username }}</dd>

                        <dt class="col-5 text-secondary">Direction</dt>
                        <dd class="col-7 text-dark mb-2">{{ $user->service?->directorate?->directorate_name ?? 'Direction Générale OIPI' }}</dd>

                        <dt class="col-5 text-secondary">Service</dt>
                        <dd class="col-7 text-dark mb-2">{{ $user->service?->service_name ?? 'Non assigné' }}</dd>

                        <dt class="col-5 text-secondary">Membre depuis</dt>
                        <dd class="col-7 text-dark mb-0">{{ $user->created_at?->format('d/m/Y') }}</dd>
                    </dl>
                </div>

            </div>

            <!-- CARTE MOT DE PASSE ET ACCES -->
            <div class="card mb-3">
                <div class="card-header py-3">
                    <h3 class="card-title">
                        <i class="ti ti-key me-2 text-primary"></i>
                        Sécurité du mot de passe
                    </h3>
                </div>
                <div class="card-body py-3">
                    <p class="text-secondary small mb-3">
                        Pour modifier votre mot de passe de connexion.
                    </p>
                    <a href="{{ route('password.change') }}" class="btn btn-outline-secondary w-100">
                        <i class="ti ti-shield-lock me-1"></i>Changer mon mot de passe
                    </a>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE : FORMULAIRE DE MISE A JOUR DU PROFIL -->
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header py-3">
                    <h3 class="card-title">
                        <i class="ti ti-edit me-2 text-primary"></i>
                        Modifier mes coordonnées personnelles
                    </h3>
                </div>
                <div class="card-body py-3">
                    <form method="post" action="{{ route('profile.update') }}" autocomplete="off" id="profile-form">
                        @csrf
                        @method('PATCH')

                        <div class="row g-3">
                            <!-- NOM -->
                            <div class="col-12 col-md-6">
                                <label class="form-label required" for="last_name">Nom</label>
                                <input
                                    type="text"
                                    class="form-control @error('last_name') is-invalid @enderror"
                                    id="last_name"
                                    name="last_name"
                                    value="{{ old('last_name', $user->last_name) }}"
                                    maxlength="100"
                                    required
                                >
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- PRENOMS -->
                            <div class="col-12 col-md-6">
                                <label class="form-label required" for="first_names">Prénoms</label>
                                <input
                                    type="text"
                                    class="form-control @error('first_names') is-invalid @enderror"
                                    id="first_names"
                                    name="first_names"
                                    value="{{ old('first_names', $user->first_names) }}"
                                    maxlength="100"
                                    required
                                >
                                @error('first_names')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- ADRESSE E-MAIL -->
                            <div class="col-12 col-md-6">
                                <label class="form-label required" for="email">Adresse e-mail</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-mail text-secondary"></i></span>
                                    <input
                                        type="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        id="email"
                                        name="email"
                                        value="{{ old('email', $user->email) }}"
                                        maxlength="150"
                                        required
                                    >
                                </div>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint small text-secondary mt-1">
                                    Utilisée pour vos notifications et la traçabilité de vos actions.
                                </div>
                            </div>

                            <!-- TELEPHONE -->
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="phone">Numéro de téléphone</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-phone text-secondary"></i></span>
                                    <input
                                        type="tel"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        id="phone"
                                        name="phone"
                                        value="{{ old('phone', $user->phone) }}"
                                        maxlength="30"
                                        placeholder="Ex. 0700000000"
                                    >
                                </div>
                                @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint small text-secondary mt-1">
                                    Optionnel · Pour les contacts opérationnels d'urgence.
                                </div>
                            </div>

                            <!-- ENCADRE DE SECURITE : CONFIRMATION DU MOT DE PASSE SI CHANGEMENT D'EMAIL -->
                            <div class="col-12">
                                <div class="card bg-light border p-3 mb-1" id="email-security-block">
                                    <div class="d-flex align-items-start gap-2 mb-2">
                                        <i class="ti ti-shield-lock fs-2 text-warning mt-1"></i>
                                        <div>
                                            <div class="fw-bold text-dark">Confirmation requise en cas de changement d'adresse e-mail</div>
                                            <div class="small text-secondary">
                                                Par mesure de sécurité, la modification de votre adresse e-mail nécessite obligatoirement la saisie de votre mot de passe de session actuel.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label" for="current_password" id="current_password_label">
                                            Mot de passe actuel <span class="text-danger d-none" id="password-required-mark">*</span>
                                        </label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon"><i class="ti ti-lock text-secondary"></i></span>
                                            <input
                                                type="password"
                                                class="form-control @error('current_password') is-invalid @enderror"
                                                id="current_password"
                                                name="current_password"
                                                placeholder="Requis uniquement si vous changez d'e-mail"
                                                autocomplete="current-password"
                                            >
                                        </div>
                                        @error('current_password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between mt-4 pt-3 border-top gap-2">
                            <div class="text-secondary small">
                                <i class="ti ti-history me-1"></i>Toute modification est enregistrée dans le journal d'audit immuable.
                            </div>
                            <button type="submit" class="btn btn-primary" id="btn-save-profile">
                                <i class="ti ti-check me-1"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const emailInput = document.getElementById('email');
    const currentPasswordInput = document.getElementById('current_password');
    const passwordRequiredMark = document.getElementById('password-required-mark');
    const originalEmail = "{{ strtolower($user->email) }}";

    function checkEmailChange() {
        const currentEmail = (emailInput.value || '').trim().toLowerCase();
        const hasChanged = currentEmail !== originalEmail;

        if (hasChanged) {
            passwordRequiredMark.classList.remove('d-none');
            currentPasswordInput.setAttribute('placeholder', 'Saisissez votre mot de passe actuel');
        } else {
            passwordRequiredMark.classList.add('d-none');
            currentPasswordInput.setAttribute('placeholder', 'Requis uniquement si vous changez d\'e-mail');
        }
    }

    emailInput.addEventListener('input', checkEmailChange);
    checkEmailChange();
});
</script>
@endpush
