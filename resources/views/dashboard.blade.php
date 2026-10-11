@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Pilotage</div>
                <h1 class="page-title">Tableau de bord</h1>
                <p class="text-secondary mb-0">Suivi national des formulaires manquants et des missions de recherche.</p>
            </div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-12 col-md-6 col-xl-3">
            <article class="card risfm-accent h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar bg-primary-lt text-primary">
                            <i class="ti ti-layout-dashboard fs-2"></i>
                        </span>
                        <div>
                            <div class="text-secondary">Interface</div>
                            <div class="h3 mb-0">Tabler initialisé</div>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>
@endsection
