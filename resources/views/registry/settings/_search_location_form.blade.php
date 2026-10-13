@php
    $item = $searchLocation ?? null;
    $isEdit = $item !== null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label required" for="location_name_{{ $item?->uuid ?? 'new' }}">Nom du lieu ou de la salle</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-map-pin text-secondary"></i></span>
            <input
                class="form-control @error('location_name') is-invalid @enderror"
                id="location_name_{{ $item?->uuid ?? 'new' }}"
                name="location_name"
                type="text"
                maxlength="150"
                placeholder="ex. Archives centrales ou Salle de tri 2"
                value="{{ old('location_name', $item?->location_name) }}"
                required
            >
        </div>
        @error('location_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label required" for="location_order_{{ $item?->uuid ?? 'new' }}">Ordre d’affichage</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-sort-ascending-numbers text-secondary"></i></span>
            <input
                class="form-control @error('display_order') is-invalid @enderror"
                id="location_order_{{ $item?->uuid ?? 'new' }}"
                name="display_order"
                type="number"
                min="0"
                max="65535"
                placeholder="10"
                value="{{ old('display_order', $item?->display_order ?? 0) }}"
                required
            >
        </div>
        @error('display_order') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label" for="description_{{ $item?->uuid ?? 'new' }}">Description ou consignes d'accès</label>
        <textarea
            class="form-control @error('description') is-invalid @enderror"
            id="description_{{ $item?->uuid ?? 'new' }}"
            name="description"
            maxlength="500"
            rows="3"
            placeholder="Précisions utiles sur le bâtiment, l'étage, l'armoire ou le responsable de la zone..."
        >{{ old('description', $item?->description) }}</textarea>
        @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
</div>
