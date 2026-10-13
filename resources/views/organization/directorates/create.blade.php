@extends('layouts.app')

@section('title', 'Ajouter une direction')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Organisation / Directions</div>
                <h1 class="page-title">Ajouter une direction</h1>
                <div class="text-secondary mt-1">Déclarez une direction générale ou opérationnelle dans l'organigramme de l'OIPI.</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a class="btn btn-outline-secondary" href="{{ route('organization.index') }}">
                    <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                    Retour à l'organigramme
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <form method="post" action="{{ route('organization.directorates.store') }}" autocomplete="off" novalidate>
                @csrf
                <div class="card">
                    <div class="card-header py-3">
                        <h3 class="card-title">
                            <i class="ti ti-building me-2 text-primary" aria-hidden="true"></i>
                            Informations de la direction
                        </h3>
                    </div>
                    <div class="card-body">
                        @include('organization.directorates._form')
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center py-3">
                        <a class="btn btn-outline-secondary" href="{{ route('organization.index') }}">
                            Annuler
                        </a>
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-check me-1" aria-hidden="true"></i>
                            Ajouter la direction
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
