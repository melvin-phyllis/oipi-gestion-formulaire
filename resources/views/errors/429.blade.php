@php
    $headers = isset($exception) && method_exists($exception, 'getHeaders')
        ? $exception->getHeaders()
        : [];
    $retryAfterHeader = $headers['Retry-After'] ?? $headers['retry-after'] ?? 60;

    if (is_array($retryAfterHeader)) {
        $retryAfterHeader = reset($retryAfterHeader);
    }

    if (is_numeric($retryAfterHeader)) {
        $retryAfter = max(1, (int) $retryAfterHeader);
    } elseif (is_string($retryAfterHeader) && strtotime($retryAfterHeader) !== false) {
        $retryAfter = max(1, strtotime($retryAfterHeader) - time());
    } else {
        $retryAfter = 60;
    }

    $isLoginRequest = request()->routeIs('login.store');
    $isPasswordResetRequest = request()->routeIs('password.email', 'password.update');

    $heading = match (true) {
        $isLoginRequest => 'Trop de tentatives de connexion',
        $isPasswordResetRequest => 'Trop de demandes de réinitialisation',
        default => 'Trop de requêtes',
    };

    $message = match (true) {
        $isLoginRequest => 'Pour protéger votre compte, les nouvelles tentatives de connexion sont temporairement suspendues.',
        $isPasswordResetRequest => 'Pour protéger votre compte, les nouvelles demandes de réinitialisation sont temporairement suspendues.',
        default => 'Cette action a été effectuée trop souvent. Patientez avant de réessayer.',
    };

    $returnUrl = match (true) {
        $isLoginRequest => route('login'),
        $isPasswordResetRequest => route('password.request'),
        auth()->check() => route('dashboard'),
        default => route('login'),
    };

    $returnLabel = match (true) {
        $isLoginRequest => 'Retour à la page de connexion',
        $isPasswordResetRequest => 'Retour au mot de passe oublié',
        auth()->check() => 'Retour au tableau de bord',
        default => 'Retour à la page de connexion',
    };
@endphp

@extends('layouts.guest')

@section('title', '429 | Trop de requêtes')

@section('content')
    <div class="card card-md risfm-auth-card">
        <div class="card-body text-center p-4 p-md-5">
            <div class="badge bg-secondary-lt text-secondary mb-3 font-monospace">
                ERREUR 429
            </div>

            <h1 class="h2 mb-2 text-dark">{{ $heading }}</h1>

            <p class="text-secondary mb-0">
                {{ $message }}
            </p>

            <div class="my-4">
                <div class="small text-secondary mb-1" id="countdown-label">
                    Vous pourrez réessayer dans
                </div>
                <div class="h1 mb-3 font-monospace d-flex align-items-center justify-content-center gap-2" role="timer" aria-labelledby="countdown-label">
                    <i class="ti ti-clock-hour-4 text-primary risfm-countdown-clock" id="countdown-clock" aria-hidden="true"></i>
                    <span id="countdown-seconds">{{ $retryAfter }}</span>
                    <span class="fs-3 fw-normal text-secondary" id="countdown-unit">secondes</span>
                </div>

                <div
                    class="progress progress-sm"
                    id="countdown-progress-container"
                    role="progressbar"
                    aria-label="Temps d’attente restant"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="100"
                >
                    <div class="progress-bar bg-primary" id="countdown-progress" style="width: 100%;"></div>
                </div>
            </div>

            <a href="{{ $returnUrl }}" class="btn btn-primary w-100">
                {{ $returnLabel }}
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ Vite::cspNonce() }}">
        document.addEventListener('DOMContentLoaded', function () {
            const totalSeconds = @json($retryAfter);
            const deadline = Date.now() + (totalSeconds * 1000);
            const secondsEl = document.getElementById('countdown-seconds');
            const unitEl = document.getElementById('countdown-unit');
            const labelEl = document.getElementById('countdown-label');
            const clockEl = document.getElementById('countdown-clock');
            const progressEl = document.getElementById('countdown-progress');
            const progressContainerEl = document.getElementById('countdown-progress-container');

            if (!secondsEl || !unitEl || !labelEl || !clockEl || !progressEl || !progressContainerEl) {
                return;
            }

            const updateCountdown = function () {
                const remainingSeconds = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                const percent = Math.max(0, Math.round((remainingSeconds / totalSeconds) * 100));

                secondsEl.textContent = remainingSeconds;
                unitEl.textContent = remainingSeconds === 1 ? 'seconde' : 'secondes';
                progressEl.style.width = percent + '%';
                progressContainerEl.setAttribute('aria-valuenow', percent);

                if (remainingSeconds === 0) {
                    labelEl.textContent = 'Vous pouvez maintenant réessayer.';
                    clockEl.classList.add('risfm-countdown-clock-stopped');
                    progressContainerEl.classList.add('d-none');

                    return true;
                }

                return false;
            };

            if (updateCountdown()) {
                return;
            }

            const timer = window.setInterval(function () {
                if (updateCountdown()) {
                    window.clearInterval(timer);
                }
            }, 1000);
        });
    </script>
@endpush

@push('styles')
    <style nonce="{{ Vite::cspNonce() }}">
        .risfm-countdown-clock {
            display: inline-block;
            animation: risfm-countdown-clock-spin 1.5s linear infinite;
        }

        .risfm-countdown-clock-stopped {
            animation: none;
        }

        @keyframes risfm-countdown-clock-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .risfm-countdown-clock {
                animation: none;
            }
        }
    </style>
@endpush
