@php
    $item = $titleType ?? null;
    $isEdit = $item !== null;
@endphp

<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label required" for="title_type_code_{{ $item?->uuid ?? 'new' }}">Abréviation / Sigle</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-hash text-secondary"></i></span>
            <input
                class="form-control text-uppercase @error('title_type_code') is-invalid @enderror"
                id="title_type_code_{{ $item?->uuid ?? 'new' }}"
                name="title_type_code"
                type="text"
                maxlength="20"
                placeholder="ex. BRV ou MARQ"
                value="{{ old('title_type_code', $item?->title_type_code) }}"
                required
            >
        </div>
        @error('title_type_code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        <div class="form-hint small text-secondary mt-1">Code court (ex. BRV, MARQ, DESS...).</div>
    </div>

    <div class="col-md-7">
        <label class="form-label required" for="display_order_{{ $item?->uuid ?? 'new' }}">Ordre d’affichage</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-sort-ascending-numbers text-secondary"></i></span>
            <input
                class="form-control @error('display_order') is-invalid @enderror"
                id="display_order_{{ $item?->uuid ?? 'new' }}"
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
        <label class="form-label required" for="title_type_name_{{ $item?->uuid ?? 'new' }}">Nom complet du type de titre</label>
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-certificate text-secondary"></i></span>
            <input
                class="form-control @error('title_type_name') is-invalid @enderror"
                id="title_type_name_{{ $item?->uuid ?? 'new' }}"
                name="title_type_name"
                type="text"
                maxlength="100"
                placeholder="ex. Brevet d'invention"
                value="{{ old('title_type_name', $item?->title_type_name) }}"
                required
            >
        </div>
        @error('title_type_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
</div>
