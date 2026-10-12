@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="carbon-pagination">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="pagination-btn disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                <span>&larr; Sebelumnya</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-btn" aria-label="@lang('pagination.previous')">
                <span>&larr; Sebelumnya</span>
            </a>
        @endif

        {{-- Pagination Elements --}}
        <div class="pagination-pages">
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="pagination-item disabled" aria-disabled="true"><span>{{ $element }}</span></span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-item active" aria-current="page"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}" class="pagination-item">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-btn" aria-label="@lang('pagination.next')">
                <span>Selanjutnya &rarr;</span>
            </a>
        @else
            <span class="pagination-btn disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                <span>Selanjutnya &rarr;</span>
            </span>
        @endif
    </nav>
@endif
