@extends('layouts.app')

@section('title', 'Ajouter un utilisateur')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Administration / Utilisateurs</div>
                <h1 class="page-title">Créer un nouveau compte</h1>
                <div class="text-secondary mt-1">
                    Enregistrez un nouvel agent, affectez son rôle système et son rattachement dans l'organigramme de l'OIPI.
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

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <form method="post" action="{{ route('users.store') }}" autocomplete="off" novalidate>
                @csrf

                <div class="card">
                    <div class="card-header py-3">
                        <h3 class="card-title">
                            <i class="ti ti-user-plus me-2 text-primary" aria-hidden="true"></i>
                            Informations personnelles & Identifiants
                        </h3>
                    </div>

                    <div class="card-body">
                        @include('administration.users._form')
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center py-3">
                        <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">
                            Annuler
                        </a>
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-check me-1" aria-hidden="true"></i>
                            Créer l'utilisateur
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ Vite::cspNonce() }}">
        document.addEventListener('DOMContentLoaded', function () {
            const generateBtn = document.getElementById('btn-generate-username');
            if (!generateBtn) return;

            const usernameInput = document.getElementById('username');
            const lastNameInput = document.getElementById('last_name');
            const firstNamesInput = document.getElementById('first_names');

            const getIcon = function () {
                return document.getElementById('btn-generate-username-icon');
            };
            const getSpinner = function () {
                return document.getElementById('btn-generate-username-spinner');
            };
            const getBtnText = function () {
                return document.getElementById('btn-generate-username-text');
            };

            function setGenerateBtnLoading() {
                generateBtn.disabled = true;
                const iconEl = getIcon();
                const spinnerEl = getSpinner();
                const textEl = getBtnText();
                if (iconEl) iconEl.classList.add('d-none');
                if (spinnerEl) spinnerEl.classList.remove('d-none');
                if (textEl) textEl.textContent = 'Génération en cours...';
            }

            function setGenerateBtnRateLimited(remaining) {
                generateBtn.disabled = true;
                const iconEl = getIcon();
                const spinnerEl = getSpinner();
                const textEl = getBtnText();
                if (spinnerEl) spinnerEl.classList.add('d-none');
                if (iconEl) {
                    iconEl.className = 'ti ti-hourglass-empty me-1';
                    iconEl.classList.remove('d-none');
                }
                if (textEl) textEl.textContent = `Patienter (${remaining}s)`;
            }

            function resetGenerateBtn() {
                generateBtn.disabled = false;
                const iconEl = getIcon();
                const spinnerEl = getSpinner();
                const textEl = getBtnText();
                if (spinnerEl) spinnerEl.classList.add('d-none');
                if (iconEl) {
                    iconEl.className = 'ti ti-sparkles me-1';
                    iconEl.classList.remove('d-none');
                }
                if (textEl) textEl.textContent = 'Générer l’identifiant';
            }

            const errorFields = {
                last_name: {
                    input: lastNameInput,
                    errorEl: document.getElementById('last_name-dynamic-error'),
                },
                first_names: {
                    input: firstNamesInput,
                    errorEl: document.getElementById('first_names-dynamic-error'),
                },
                username: {
                    input: usernameInput,
                    errorEl: document.getElementById('username-dynamic-error'),
                },
            };

            function clearDynamicErrors() {
                Object.values(errorFields).forEach(function (field) {
                    if (field.input) {
                        field.input.classList.remove('is-invalid');
                    }
                    if (field.errorEl) {
                        field.errorEl.textContent = '';
                        field.errorEl.classList.remove('d-block');
                    }
                });
            }

            function handleRateLimit(options) {
                if (typeof window.risfmHandleRateLimit === 'function') {
                    return window.risfmHandleRateLimit(options);
                }

                const headerRetryAfter = options.response?.headers
                    ? (typeof options.response.headers.get === 'function'
                        ? (options.response.headers.get('Retry-After') || options.response.headers.get('retry-after'))
                        : (options.response.headers['Retry-After'] || options.response.headers['retry-after']))
                    : null;

                let retryAfter = Number(options.data?.retry_after ?? options.data?.retryAfter ?? headerRetryAfter);
                if (!Number.isFinite(retryAfter) || retryAfter <= 0) {
                    retryAfter = 60;
                }
                retryAfter = Math.ceil(retryAfter);

                const message = options.data?.message || `Trop de requêtes ont été effectuées. Veuillez patienter ${retryAfter} seconde(s) avant de renouveler l'action.`;

                const btn = options.button;
                if (btn) {
                    if (btn._risfmRateLimitTimer) {
                        clearInterval(btn._risfmRateLimitTimer);
                        btn._risfmRateLimitTimer = null;
                    }
                    btn._isRateLimited = true;
                    btn.disabled = true;

                    if (typeof options.renderButton === 'function') {
                        options.renderButton(btn, retryAfter);
                    } else {
                        if (typeof btn._risfmOriginalHtml !== 'string') {
                            btn._risfmOriginalHtml = btn.innerHTML;
                        }
                        btn.innerHTML = `<i class="ti ti-hourglass-empty me-1" aria-hidden="true"></i> Patienter (${retryAfter}s)`;
                    }

                    let remaining = retryAfter;
                    const timer = setInterval(function () {
                        remaining--;
                        if (remaining <= 0) {
                            clearInterval(timer);
                            btn._risfmRateLimitTimer = null;
                            btn._isRateLimited = false;
                            btn.disabled = false;
                            if (typeof options.restoreButton === 'function') {
                                options.restoreButton(btn);
                            } else if (typeof btn._risfmOriginalHtml === 'string') {
                                btn.innerHTML = btn._risfmOriginalHtml;
                                delete btn._risfmOriginalHtml;
                            }
                            if (typeof options.onFinish === 'function') {
                                options.onFinish();
                            }
                        } else {
                            if (typeof options.renderButton === 'function') {
                                options.renderButton(btn, remaining);
                            } else {
                                btn.innerHTML = `<i class="ti ti-hourglass-empty me-1" aria-hidden="true"></i> Patienter (${remaining}s)`;
                            }
                            if (typeof options.onTick === 'function') {
                                options.onTick(remaining);
                            }
                        }
                    }, 1000);
                    btn._risfmRateLimitTimer = timer;
                }

                if (typeof options.onAlert === 'function') {
                    options.onAlert(message, retryAfter);
                }

                return retryAfter;
            }

            [lastNameInput, firstNamesInput].forEach(function (input) {
                if (!input) return;
                input.addEventListener('input', function () {
                    this.classList.remove('is-invalid');
                    const errorEl = document.getElementById(this.id + '-dynamic-error');
                    if (errorEl) {
                        errorEl.textContent = '';
                        errorEl.classList.remove('d-block');
                    }
                });
            });

            generateBtn.addEventListener('click', async function () {
                clearDynamicErrors();

                const lastName = lastNameInput ? lastNameInput.value.trim() : '';
                const firstNames = firstNamesInput ? firstNamesInput.value.trim() : '';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                setGenerateBtnLoading();

                try {
                    const response = await fetch("{{ route('users.username-suggestion') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            first_names: firstNames,
                            last_name: lastName,
                        }),
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data && data.username && usernameInput) {
                            usernameInput.value = data.username;
                            usernameInput.classList.remove('is-invalid');
                            usernameInput.dispatchEvent(new Event('input', { bubbles: true }));
                            usernameInput.focus();
                        }
                    } else if (response.status === 429) {
                        const data = await response.json().catch(function () { return {}; });

                        handleRateLimit({
                            response,
                            data,
                            button: generateBtn,
                            renderButton: function (btn, remaining) {
                                setGenerateBtnRateLimited(remaining);
                            },
                            restoreButton: function (btn) {
                                resetGenerateBtn();
                            },
                            onAlert: function (msg) {
                                const usernameTarget = errorFields.username;
                                if (usernameTarget && usernameTarget.errorEl) {
                                    usernameTarget.errorEl.textContent = msg;
                                    usernameTarget.errorEl.classList.add('d-block');
                                }
                            }
                        });
                        return;
                    } else if (response.status === 422) {
                        const data = await response.json();
                        if (data && data.errors) {
                            Object.entries(data.errors).forEach(function ([field, messages]) {
                                const target = errorFields[field];
                                if (target && target.input) {
                                    target.input.classList.add('is-invalid');
                                    if (target.errorEl && Array.isArray(messages) && messages.length > 0) {
                                        target.errorEl.textContent = messages[0];
                                        target.errorEl.classList.add('d-block');
                                    }
                                }
                            });
                        }
                    } else {
                        const errorData = await response.json().catch(function () { return {}; });
                        const message = errorData.message || 'Impossible de générer l’identifiant pour le moment.';
                        const usernameTarget = errorFields.username;
                        if (usernameTarget && usernameTarget.errorEl) {
                            usernameTarget.errorEl.textContent = message;
                            usernameTarget.errorEl.classList.add('d-block');
                        }
                    }
                } catch (error) {
                    const usernameTarget = errorFields.username;
                    if (usernameTarget && usernameTarget.errorEl) {
                        usernameTarget.errorEl.textContent = 'Une erreur réseau est survenue lors de la génération.';
                        usernameTarget.errorEl.classList.add('d-block');
                    }
                } finally {
                    if (!generateBtn._isRateLimited) {
                        resetGenerateBtn();
                    }
                }
            });
        });
    </script>
@endpush
