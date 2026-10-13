@extends('layouts.app')

@section('title', 'Modifier l’utilisateur')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <h1 class="page-title d-flex align-items-center gap-2">
                    <span>{{ $managedUser->first_names }} {{ $managedUser->last_name }}</span>
                    @if ($managedUser->is_active)
                        <span class="badge bg-success-lt fs-6">Actif</span>
                    @else
                        <span class="badge bg-secondary-lt fs-6">Inactif</span>
                    @endif
                </h1>
                <div class="text-secondary mt-1">
                    Identifiant : <code>{{ $managedUser->username }}</code> · Créé le {{ $managedUser->created_at?->format('d/m/Y') }}
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">
                    <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                    Retour à la liste
                </a>
            </div>
        </div>
    </div>

    @if (session('temporary_password'))
        <div class="alert alert-important alert-warning alert-dismissible mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="ti ti-key fs-1 me-3" aria-hidden="true"></i>
                <div class="flex-grow-1">
                    <h3 class="mb-1">Nouveau mot de passe temporaire généré !</h3>
                    <p class="mb-2">Communiquez ce mot de passe à l'agent de manière sécurisée. Il lui sera demandé de le changer immédiatement à la connexion.</p>
                    <div class="d-inline-flex align-items-center gap-2 flex-wrap">
                        <div
                            class="d-inline-flex align-items-center bg-white text-dark px-3 py-2 rounded border border-warning font-monospace fs-3 fw-bold user-select-all cursor-pointer shadow-sm"
                            id="temporary-password-container"
                            role="button"
                            tabindex="0"
                            title="Cliquer pour copier le mot de passe"
                            aria-label="Cliquer pour copier le mot de passe temporaire"
                            data-copy-value="{{ session('temporary_password') }}"
                        >
                            <span>{{ session('temporary_password') }}</span>
                            <i class="ti ti-copy ms-2 text-secondary fs-2" id="icon-copy-temporary-password" aria-hidden="true"></i>
                        </div>
                        <span class="badge bg-success text-white d-none" id="copy-success-badge" role="status" aria-live="polite">
                            <i class="ti ti-check me-1" aria-hidden="true"></i> Copié !
                        </span>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- FORMULAIRE PRINCIPAL -->
        <div class="col-12 col-xl-8">
            <form method="post" action="{{ route('users.update', $managedUser) }}" autocomplete="off" novalidate>
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header py-3">
                        <h3 class="card-title">
                            <i class="ti ti-user-edit me-2 text-primary" aria-hidden="true"></i>
                            Informations du compte et affectation
                        </h3>
                    </div>
                    <div class="card-body">
                        @include('administration.users._form')
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">
                            Annuler
                        </a>
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-device-floppy me-1" aria-hidden="true"></i>
                            Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ZONE ACTIONS SENSIBLES / SÉCURITÉ -->
        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-status-top bg-warning"></div>
                <div class="card-header py-3">
                    <h3 class="card-title text-dark">
                        <i class="ti ti-shield-lock me-2 text-warning" aria-hidden="true"></i>
                        Sécurité du compte
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-secondary small">
                        La réinitialisation génère un <strong>mot de passe temporaire</strong>, révoque immédiatement toutes les sessions actives de l'agent et exige un renouvellement dès sa reconnexion.
                    </p>

                    <form method="post" action="{{ route('users.password.reset', $managedUser) }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label required" for="current_password">
                                Confirmez votre mot de passe (Administrateur)
                            </label>
                            <div class="input-group input-group-flat @error('current_password') is-invalid @enderror">
                                <span class="input-group-text">
                                    <i class="ti ti-lock text-secondary"></i>
                                </span>
                                <input
                                    class="form-control @error('current_password') is-invalid @enderror"
                                    id="current_password"
                                    name="current_password"
                                    type="password"
                                    placeholder="Votre mot de passe"
                                    autocomplete="current-password"
                                    required
                                >
                                <span class="input-group-text">
                                    <button
                                        type="button"
                                        class="input-group-link border-0 bg-transparent p-0"
                                        title="Afficher/masquer"
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

                        <button class="btn btn-warning w-100" type="submit">
                            <i class="ti ti-refresh me-1" aria-hidden="true"></i>
                            Générer un mot de passe temporaire
                        </button>
                    </form>
                </div>
            </div>

            <!-- CARTE RECAP STATUT -->
            <div class="card">
                <div class="card-header py-3">
                    <h3 class="card-title text-secondary">
                        <i class="ti ti-info-circle me-2" aria-hidden="true"></i>
                        Détails d'activité
                    </h3>
                </div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-6 text-secondary">Dernière connexion</dt>
                        <dd class="col-6 text-end fw-medium">
                            {{ $managedUser->last_login_at ? $managedUser->last_login_at->format('d/m/Y H:i') : 'Jamais' }}
                        </dd>

                        <dt class="col-6 text-secondary">Changement requis</dt>
                        <dd class="col-6 text-end">
                            @if ($managedUser->must_change_password)
                                <span class="badge bg-warning-lt">Oui</span>
                            @else
                                <span class="badge bg-success-lt">Non</span>
                            @endif
                        </dd>

                        <dt class="col-6 text-secondary">Version de session</dt>
                        <dd class="col-6 text-end text-muted font-monospace">
                            #{{ $managedUser->session_version }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection

@if (session('temporary_password'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.getElementById('temporary-password-container');
                if (!container) return;

                const copyIcon = document.getElementById('icon-copy-temporary-password');
                const successBadge = document.getElementById('copy-success-badge');
                let resetTimeout = null;

                async function copyPassword() {
                    const text = container.getAttribute('data-copy-value') || '';
                    if (!text) return;

                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(text);
                        } else {
                            const textarea = document.createElement('textarea');
                            textarea.value = text;
                            textarea.style.position = 'fixed';
                            textarea.style.opacity = '0';
                            document.body.appendChild(textarea);
                            textarea.focus();
                            textarea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textarea);
                        }

                        if (copyIcon) {
                            copyIcon.className = 'ti ti-check text-success fs-2 ms-2';
                        }
                        if (successBadge) {
                            successBadge.classList.remove('d-none');
                        }

                        clearTimeout(resetTimeout);
                        resetTimeout = setTimeout(function () {
                            if (copyIcon) {
                                copyIcon.className = 'ti ti-copy ms-2 text-secondary fs-2';
                            }
                            if (successBadge) {
                                successBadge.classList.add('d-none');
                            }
                        }, 2500);
                    } catch (err) {
                        console.error('Erreur lors de la copie du mot de passe :', err);
                    }
                }

                container.addEventListener('click', copyPassword);
                container.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        copyPassword();
                    }
                });
            });
        </script>
    @endpush
@endif
