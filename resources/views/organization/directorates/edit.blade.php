@extends('layouts.app')

@section('title', 'Modifier une direction')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Organisation / Directions</div>
                <h1 class="page-title d-flex align-items-center gap-2">
                    <span>Modifier la direction</span>
                    <span class="badge bg-primary-lt fs-6">{{ $directorate->directorate_code }}</span>
                </h1>
                <div class="text-secondary mt-1">{{ $directorate->directorate_name }}</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a class="btn btn-outline-secondary" href="{{ route('organization.index') }}#directorate-{{ $directorate->uuid }}">
                    <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                    Retour à l'organigramme
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <form method="post" action="{{ route('organization.directorates.update', $directorate) }}" autocomplete="off" novalidate>
                @csrf
                @method('PUT')
                <div class="card">
                    <div class="card-header py-3">
                        <h3 class="card-title">
                            <i class="ti ti-edit me-2 text-primary" aria-hidden="true"></i>
                            Informations de la direction
                        </h3>
                    </div>
                    <div class="card-body">
                        @include('organization.directorates._form')
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center py-3">
                        <a class="btn btn-outline-secondary" href="{{ route('organization.index') }}#directorate-{{ $directorate->uuid }}">
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
