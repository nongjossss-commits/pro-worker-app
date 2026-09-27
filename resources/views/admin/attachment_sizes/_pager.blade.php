{{-- Page numbers only (the "Showing x–y of z" line is translated in _list). --}}
@if ($paginator->hasPages())
    <nav aria-label="pages">
        <ul class="pagination pagination-sm mb-0 flex-wrap">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if ($paginator->onFirstPage())
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('Previous') }}">&lsaquo;</a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                @if ($paginator->hasMorePages())
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('Next') }}">&rsaquo;</a>
                @else
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
