@php($currentService = $managedService ?? null)

<div class="row g-3">
    <div class="col-12">
        <label class="form-label required" for="directorate_code_unique">Direction de rattachement</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-building text-secondary"></i></span>
            <select
                class="form-select @error('directorate_code_unique') is-invalid @enderror"
                id="directorate_code_unique"
                name="directorate_code_unique"
                required
            >
                <option value="">Sélectionner une direction</option>
                @foreach ($directorates as $directorate)
                    <option
                        value="{{ $directorate->directorate_code_unique }}"
                        @selected(old('directorate_code_unique', $currentService?->directorate_code_unique ?? ($selectedDirectorate ?? null)?->directorate_code_unique) === $directorate->directorate_code_unique)
                    >
                        {{ $directorate->directorate_name }} ({{ $directorate->directorate_code }})
                    </option>
                @endforeach
            </select>
        </div>
        @error('directorate_code_unique') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label required" for="service_code">Code du service</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-hash text-secondary"></i></span>
            <input
                class="form-control text-uppercase @error('service_code') is-invalid @enderror"
                id="service_code"
                name="service_code"
                type="text"
                maxlength="40"
                placeholder="ex. SDSID ou SCE_JURIDIQUE"
                value="{{ old('service_code', $currentService?->service_code) }}"
                required
            >
        </div>
        @error('service_code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        <div class="form-hint small text-secondary mt-1">Identifiant unique du service.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="abbreviation">Abréviation / Sigle</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-typography text-secondary"></i></span>
            <input
                class="form-control text-uppercase @error('abbreviation') is-invalid @enderror"
                id="abbreviation"
                name="abbreviation"
                type="text"
                maxlength="30"
                placeholder="ex. SDSID"
                value="{{ old('abbreviation', $currentService?->abbreviation) }}"
            >
        </div>
        @error('abbreviation') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label required" for="display_order">Ordre d’affichage</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-sort-ascending-numbers text-secondary"></i></span>
            <input
                class="form-control @error('display_order') is-invalid @enderror"
                id="display_order"
                name="display_order"
                type="number"
                min="0"
                max="65535"
                placeholder="10"
                value="{{ old('display_order', $currentService?->display_order ?? 0) }}"
                required
            >
        </div>
        @error('display_order') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label required" for="service_name">Nom complet du service</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-folder text-secondary"></i></span>
            <input
                class="form-control @error('service_name') is-invalid @enderror"
                id="service_name"
                name="service_name"
                type="text"
                maxlength="200"
                placeholder="ex. Sous-direction du système d’information et de la documentation"
                value="{{ old('service_name', $currentService?->service_name) }}"
                required
            >
        </div>
        @error('service_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
</div>
