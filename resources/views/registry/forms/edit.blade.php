@extends('layouts.app')

@section('title', 'Modifier le formulaire ' . $missingForm->registry_number)

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <h1 class="page-title d-flex align-items-center gap-2">
                    <span>Modifier les métadonnées</span>
                    <span class="badge bg-primary-lt fs-6">{{ $missingForm->registry_number }}</span>
                </h1>
                <div class="text-secondary mt-1">
                    Mise à jour des informations administratives du dossier sans altérer le cycle de recherche en cours.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a class="btn btn-outline-secondary" href="{{ route('registry.forms.show', $missingForm) }}">
                    <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                    Retour au dossier
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">
            <form method="post" action="{{ route('registry.forms.update', $missingForm) }}" autocomplete="off" novalidate>
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header py-3">
                        <h3 class="card-title">
                            <i class="ti ti-edit me-2 text-primary" aria-hidden="true"></i>
                            Métadonnées du formulaire
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <!-- TYPE DE TITRE -->
                            <div class="col-md-6">
                                <label class="form-label required" for="title_type_code_unique">Type de titre de propriété</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-certificate text-secondary"></i></span>
                                    <select
                                        class="form-select @error('title_type_code_unique') is-invalid @enderror"
                                        id="title_type_code_unique"
                                        name="title_type_code_unique"
                                        required
                                    >
                                        @foreach ($titleTypes as $titleType)
                                            <option
                                                value="{{ $titleType->title_type_code_unique }}"
                                                @selected(old('title_type_code_unique', $missingForm->title_type_code_unique) === $titleType->title_type_code_unique)
                                            >
                                                {{ $titleType->title_type_code }} — {{ $titleType->title_type_name }}
                                                @if (! $titleType->is_active) (Désactivé) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('title_type_code_unique') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <!-- ANNEE -->
                            <div class="col-md-3">
                                <label class="form-label required" for="form_year">Année du formulaire</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-calendar text-secondary"></i></span>
                                    <input
                                        class="form-control @error('form_year') is-invalid @enderror"
                                        id="form_year"
                                        name="form_year"
                                        type="number"
                                        min="1900"
                                        max="{{ date('Y') }}"
                                        value="{{ old('form_year', $missingForm->form_year) }}"
                                        required
                                    >
                                </div>
                                @error('form_year') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <!-- PRIORITE -->
                            <div class="col-md-3">
                                <label class="form-label required" for="priority">Priorité</label>
                                <select
                                    class="form-select @error('priority') is-invalid @enderror"
                                    id="priority"
                                    name="priority"
                                    required
                                >
                                    @foreach ($priorities as $priority)
                                        <option
                                            value="{{ $priority->value }}"
                                            @selected(old('priority', $missingForm->priority->value) === $priority->value)
                                        >
                                            {{ $priority->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <!-- NUMERO DU FORMULAIRE -->
                            <div class="col-md-6">
                                <label class="form-label required" for="form_number">Numéro du formulaire</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-hash text-secondary"></i></span>
                                    <input
                                        class="form-control @error('form_number') is-invalid @enderror"
                                        id="form_number"
                                        name="form_number"
                                        type="text"
                                        maxlength="60"
                                        value="{{ old('form_number', $missingForm->form_number) }}"
                                        required
                                    >
                                </div>
                                @error('form_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center py-3">
                        <a class="btn btn-outline-secondary" href="{{ route('registry.forms.show', $missingForm) }}">
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
    </div>
@endsection

