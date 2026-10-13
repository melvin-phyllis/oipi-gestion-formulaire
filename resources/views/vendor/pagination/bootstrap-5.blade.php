@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="d-flex flex-wrap align-items-center justify-content-between w-100 gap-2">
        <p class="m-0 text-secondary small">
            Affichage de <span class="fw-semibold text-dark">{{ $paginator->firstItem() }}</span>
            à <span class="fw-semibold text-dark">{{ $paginator->lastItem() }}</span>
            sur <span class="fw-semibold text-dark">{{ number_format($paginator->total(), 0, ',', ' ') }}</span>
            {{ $paginator->total() > 1 ? 'résultats' : 'résultat' }}
        </p>

        <ul class="pagination m-0 ms-auto">
            {{-- Lien page précédente --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="Précédent">
                    <span class="page-link" aria-hidden="true">
                        <i class="ti ti-chevron-left"></i>
                        <span class="d-none d-sm-inline ms-1">Précédent</span>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Précédent">
                        <i class="ti ti-chevron-left"></i>
                        <span class="d-none d-sm-inline ms-1">Précédent</span>
                    </a>
                </li>
            @endif

            {{-- Numéros de pages --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled d-none d-md-inline-block" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Lien page suivante --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Suivant">
                        <span class="d-none d-sm-inline me-1">Suivant</span>
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="Suivant">
                    <span class="page-link" aria-hidden="true">
                        <span class="d-none d-sm-inline me-1">Suivant</span>
                        <i class="ti ti-chevron-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif

