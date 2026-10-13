@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="d-flex align-items-center justify-content-between w-100">
        <ul class="pagination m-0 ms-auto">
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">
                        <i class="ti ti-chevron-left me-1"></i>Précédent
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <i class="ti ti-chevron-left me-1"></i>Précédent
                    </a>
                </li>
            @endif

            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        Suivant<i class="ti ti-chevron-right ms-1"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">
                        Suivant<i class="ti ti-chevron-right ms-1"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif

