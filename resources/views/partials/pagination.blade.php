@if ($paginator->hasPages())
<nav>
    <ul class="pagination-custom">
        @if ($paginator->onFirstPage())
            <li class="disabled"><span>→</span></li>
        @else
            <li><a href="#" onclick="event.preventDefault(); loadPlaylists({{ $paginator->currentPage() - 1 }})">→</a></li>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="dots"><span>{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach (array_reverse($element, true) as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="active"><span>{{ $page }}</span></li>
                    @else
                        <li><a href="#" onclick="event.preventDefault(); loadPlaylists({{ $page }})">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <li><a href="#" onclick="event.preventDefault(); loadPlaylists({{ $paginator->currentPage() + 1 }})">←</a></li>
        @else
            <li class="disabled"><span>←</span></li>
        @endif
    </ul>
</nav>
@endif
