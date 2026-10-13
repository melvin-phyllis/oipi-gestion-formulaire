@php($managedDirectorate = $directorate ?? null)

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label required" for="directorate_code">Code de la direction</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-hash text-secondary"></i></span>
            <input
                class="form-control text-uppercase @error('directorate_code') is-invalid @enderror"
                id="directorate_code"
                name="directorate_code"
                type="text"
                maxlength="40"
                placeholder="ex. DG ou DSIDS"
                value="{{ old('directorate_code', $managedDirectorate?->directorate_code) }}"
                required
            >
        </div>
        @error('directorate_code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        <div class="form-hint small text-secondary mt-1">Identifiant court en lettres majuscules.</div>
    </div>

    <div class="col-md-8">
        <label class="form-label required" for="directorate_name">Nom complet de la direction</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-building text-secondary"></i></span>
            <input
                class="form-control @error('directorate_name') is-invalid @enderror"
                id="directorate_name"
                name="directorate_name"
                type="text"
                maxlength="200"
                placeholder="ex. Direction générale"
                value="{{ old('directorate_name', $managedDirectorate?->directorate_name) }}"
                required
            >
        </div>
        @error('directorate_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
                value="{{ old('display_order', $managedDirectorate?->display_order ?? 0) }}"
                required
            >
        </div>
        @error('display_order') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        <div class="form-hint small text-secondary mt-1">Définit le rang dans l'arborescence (ex. 10, 20...).</div>
    </div>
</div>
