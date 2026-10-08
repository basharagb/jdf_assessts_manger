@if ($paginator->hasPages())
    <div class="pagination-arabic" role="navigation" aria-label="التنقل بين الصفحات">
        @if ($paginator->onFirstPage())
            <span class="pagination-edge is-disabled">السابق</span>
        @else
            <a class="pagination-edge" href="{{ $paginator->previousPageUrl() }}" rel="prev">السابق</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination-dots">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination-number is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination-number" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pagination-edge" href="{{ $paginator->nextPageUrl() }}" rel="next">التالي</a>
        @else
            <span class="pagination-edge is-disabled">التالي</span>
        @endif
    </div>
@endif
