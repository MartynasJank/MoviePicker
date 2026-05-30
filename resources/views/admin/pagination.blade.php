@if ($paginator->hasPages())
<nav class="flex items-center justify-between gap-2 text-sm mt-4">
    <div>
        @if ($paginator->onFirstPage())
            <span class="px-3 py-1.5 rounded-md text-gray-600 cursor-not-allowed">« Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 rounded-md text-gray-400 hover:text-white hover:bg-white/10 transition-colors">« Previous</a>
        @endif
    </div>

    <div class="flex items-center gap-1">
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 py-1 text-gray-600">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-3 py-1.5 rounded-md bg-accent/20 text-accent font-medium">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-3 py-1.5 rounded-md text-gray-400 hover:text-white hover:bg-white/10 transition-colors">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
    </div>

    <div>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 rounded-md text-gray-400 hover:text-white hover:bg-white/10 transition-colors">Next »</a>
        @else
            <span class="px-3 py-1.5 rounded-md text-gray-600 cursor-not-allowed">Next »</span>
        @endif
    </div>
</nav>
@endif
