@extends('layouts.app')

@section('title', 'Ajouter un service')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Organisation / Services</div>
                <h1 class="page-title d-flex align-items-center gap-2">
                    <span>Ajouter un service</span>
                    <span class="badge bg-primary-lt fs-6">{{ $selectedDirectorate->directorate_code }}</span>
                </h1>
                <div class="text-secondary mt-1">Rattaché à la direction : <strong>{{ $selectedDirectorate->directorate_name }}</strong></div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a class="btn btn-outline-secondary" href="{{ route('organization.index') }}#directorate-{{ $selectedDirectorate->uuid }}">
                    <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                    Retour à l'organigramme
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <form method="post" action="{{ route('organization.services.store') }}" autocomplete="off" novalidate>
                @csrf
                <div class="card">
                    <div class="card-header py-3">
                        <h3 class="card-title">
                            <i class="ti ti-folder-plus me-2 text-primary" aria-hidden="true"></i>
                            Informations du service
                        </h3>
                    </div>
                    <div class="card-body">
                        @include('organization.services._form')
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center py-3">
                        <a class="btn btn-outline-secondary" href="{{ route('organization.index') }}#directorate-{{ $selectedDirectorate->uuid }}">
                            Annuler
                        </a>
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-check me-1" aria-hidden="true"></i>
                            Ajouter le service
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
