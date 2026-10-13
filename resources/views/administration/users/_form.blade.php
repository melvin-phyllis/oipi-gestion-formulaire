@php
    $currentUser = $managedUser ?? null;
    $servicesByDirectorate = $services->groupBy(
        fn ($service) => $service->directorate?->directorate_name ?? 'Sans direction',
    );
@endphp

<div class="row g-3">
    <!-- NOM & PRÉNOMS -->
    <div class="col-md-6">
        <label class="form-label required" for="last_name">Nom</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-id text-secondary"></i></span>
            <input
                class="form-control @error('last_name') is-invalid @enderror"
                id="last_name"
                name="last_name"
                type="text"
                maxlength="100"
                placeholder="ex. KOUASSI"
                value="{{ old('last_name', $currentUser?->last_name) }}"
                required
            >
        </div>
        <div class="invalid-feedback" id="last_name-dynamic-error"></div>
        @error('last_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label required" for="first_names">Prénoms</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-user text-secondary"></i></span>
            <input
                class="form-control @error('first_names') is-invalid @enderror"
                id="first_names"
                name="first_names"
                type="text"
                maxlength="100"
                placeholder="ex. Awa Marie"
                value="{{ old('first_names', $currentUser?->first_names) }}"
                required
            >
        </div>
        <div class="invalid-feedback" id="first_names-dynamic-error"></div>
        @error('first_names') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <!-- IDENTIFIANT & EMAIL -->
    <div class="col-md-6">
        <label class="form-label {{ $currentUser ? 'required' : '' }}" for="username">Identifiant de connexion</label>
        @if (! $currentUser)
            <div class="input-group">
                <span class="input-group-text"><i class="ti ti-at text-secondary"></i></span>
                <input
                    class="form-control @error('username') is-invalid @enderror"
                    id="username"
                    name="username"
                    type="text"
                    maxlength="50"
                    autocomplete="off"
                    placeholder="ex. awa.kouassi"
                    value="{{ old('username', $currentUser?->username) }}"
                >
                <button
                    type="button"
                    class="btn btn-outline-primary"
                    id="btn-generate-username"
                    title="Générer automatiquement l’identifiant à partir du nom et des prénoms"
                >
                    <i class="ti ti-sparkles me-1" id="btn-generate-username-icon" aria-hidden="true"></i>
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="btn-generate-username-spinner" role="status" aria-hidden="true"></span>
                    <span id="btn-generate-username-text">Générer l’identifiant</span>
                </button>
            </div>
            <div class="invalid-feedback" id="username-dynamic-error"></div>
            @error('username') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            <div class="form-hint small text-secondary mt-1">
                Laissez vide pour générer automatiquement à l’enregistrement, ou modifiez-le librement.
            </div>
        @else
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-at text-secondary"></i></span>
                <input
                    class="form-control @error('username') is-invalid @enderror"
                    id="username"
                    name="username"
                    type="text"
                    maxlength="50"
                    autocomplete="off"
                    placeholder="ex. awa.kouassi"
                    value="{{ old('username', $currentUser?->username) }}"
                    required
                >
            </div>
            @error('username') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @endif
    </div>

    <div class="col-md-6">
        <label class="form-label required" for="email">Adresse e-mail professionnelle</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-mail text-secondary"></i></span>
            <input
                class="form-control @error('email') is-invalid @enderror"
                id="email"
                name="email"
                type="email"
                maxlength="150"
                placeholder="awa.kouassi@oipi.ci"
                value="{{ old('email', $currentUser?->email) }}"
                required
            >
        </div>
        @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <!-- TÉLÉPHONE & RÔLE -->
    <div class="col-md-6">
        <label class="form-label" for="phone">Numéro de téléphone</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-phone text-secondary"></i></span>
            <input
                class="form-control @error('phone') is-invalid @enderror"
                id="phone"
                name="phone"
                type="tel"
                maxlength="30"
                placeholder="0700000000"
                value="{{ old('phone', $currentUser?->phone) }}"
            >
        </div>
        @error('phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label required" for="role_code_unique">Rôle et habilitation</label>
        <select
            class="form-select @error('role_code_unique') is-invalid @enderror"
            id="role_code_unique"
            name="role_code_unique"
            required
        >
            <option value="">Sélectionner un rôle</option>
            @foreach ($roles as $role)
                <option
                    value="{{ $role->role_code_unique }}"
                    @selected(old('role_code_unique', $currentUser?->role_code_unique) === $role->role_code_unique)
                >
                    {{ $role->role_name }}
                </option>
            @endforeach
        </select>
        @error('role_code_unique') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <!-- SERVICE / DIRECTION -->
    <div class="col-12">
        <label class="form-label required" for="service_code_unique">Rattachement organisationnel</label>
        <select
            class="form-select @error('service_code_unique') is-invalid @enderror"
            id="service_code_unique"
            name="service_code_unique"
            required
        >
            <option value="">Sélectionner un service</option>
            @foreach ($servicesByDirectorate as $directorateName => $directorateServices)
                <optgroup label="Direction : {{ $directorateName }}">
                    @foreach ($directorateServices as $service)
                        <option
                            value="{{ $service->service_code_unique }}"
                            @selected(old('service_code_unique', $currentUser?->service_code_unique) === $service->service_code_unique)
                        >
                            {{ $service->service_name }} @if ($service->abbreviation) ({{ $service->abbreviation }}) @endif
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('service_code_unique') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <!-- MOT DE PASSE (CREATION SEULEMENT) -->
    @if (! $currentUser)
        <div class="col-12 mt-3">
            <div class="p-3 rounded border bg-light d-flex align-items-center" role="status">
                <i class="ti ti-shield-lock fs-2 me-2 text-primary" aria-hidden="true"></i>
                <div class="text-dark fw-medium small">
                    Un mot de passe temporaire sera généré automatiquement.
                </div>
            </div>
        </div>
    @endif
</div>
