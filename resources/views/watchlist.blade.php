@extends('layouts.app')
@section('page_title', 'My Watchlist — MoviePickr')
@section('footer_pb', 'pb-32')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 pb-24">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white mb-4">My Watchlist <span id="wl-count" class="text-gray-500 font-normal text-lg">({{ $items->count() }})</span></h1>
        @if($items->isNotEmpty())
            <div class="flex flex-col sm:flex-row gap-2">
                @if($genres->isNotEmpty())
                    <div class="flex-1">
                        <select id="genre-select" multiple placeholder="Filter by genre...">
                            @foreach($genres as $genre)
                                <option value="{{ $genre }}">{{ $genre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <select id="sort-select" class="input-dark text-sm flex-shrink-0 sm:w-44">
                    <option value="date-desc">Newest Added</option>
                    <option value="date-asc">Oldest Added</option>
                    <option value="title-asc">Title A–Z</option>
                    <option value="title-desc">Title Z–A</option>
                    <option value="year-desc">Year (Newest)</option>
                    <option value="year-asc">Year (Oldest)</option>
                    <option value="rating-desc">Rating (High–Low)</option>
                    <option value="rating-asc">Rating (Low–High)</option>
                </select>
            </div>
        @endif
    </div>

    @if($items->isEmpty())
        <div class="text-center py-20">
            <p class="text-gray-500 text-lg mb-2">Your watchlist is empty.</p>
            <p class="text-gray-600 text-sm mb-6">Save movies from the movie page to add them here.</p>
            <a href="/movie?i=new" class="btn-accent long-single">Pick a Movie</a>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            @foreach($items as $item)
                <div class="watchlist-card"
                     data-status="{{ $item->status }}"
                     data-genres="{{ $item->genres }}"
                     data-date="{{ $item->created_at->timestamp }}"
                     data-title="{{ $item->title }}"
                     data-year="{{ $item->year ?? 0 }}"
                     data-rating="{{ $item->vote_average ?? 0 }}">

                    {{-- Poster --}}
                    <a href="{{ url('movie/'.$item->tmdb_id) }}" class="block group">
                        <div class="aspect-[2/3] rounded-xl overflow-hidden relative bg-white/[0.03]">
                            @if($item->poster_path)
                                <img src="https://image.tmdb.org/t/p/w500{{ $item->poster_path }}"
                                    alt="{{ $item->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs text-center px-3">No poster</div>
                            @endif

                            {{-- Title gradient --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/10 to-transparent pointer-events-none">
                                <div class="absolute bottom-0 left-0 right-0 p-3">
                                    <h4 class="text-sm font-semibold text-white leading-snug line-clamp-2">{{ $item->title }}</h4>
                                    <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">
                                        {{ $item->year }}@if($item->genres) · {{ $item->genres }}@endif
                                    </p>
                                </div>
                            </div>

                            {{-- Score badge --}}
                            @if($item->vote_average)
                                <div class="absolute top-2 left-2 bg-black/70 text-accent text-xs font-semibold px-1.5 py-0.5 rounded pointer-events-none">
                                    ★ {{ number_format($item->vote_average, 1) }}
                                </div>
                            @endif

                            {{-- Watched badge --}}
                            <div class="watched-overlay absolute inset-0 bg-black/50 flex items-center justify-center {{ $item->status === 'watched' ? '' : 'hidden' }} pointer-events-none">
                                <span class="bg-black/60 text-white text-xs font-medium px-3 py-1.5 rounded-full border border-white/20">✓ Watched</span>
                            </div>
                        </div>
                    </a>

                    {{-- Action buttons --}}
                    <div class="flex gap-1.5 mt-2">
                        <button class="toggle-watched flex-1 text-xs py-2 rounded-lg transition-all text-center
                            {{ $item->status === 'watched'
                                ? 'bg-white/10 text-white hover:bg-white/5 hover:text-gray-400'
                                : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white' }}"
                            data-tmdb-id="{{ $item->tmdb_id }}"
                            data-status="{{ $item->status }}">
                            {{ $item->status === 'watched' ? '✓ Watched' : 'Mark watched' }}
                        </button>
                        <button class="remove-from-watchlist text-xs py-2 px-3 rounded-lg bg-white/5 text-gray-500 hover:bg-red-900/30 hover:text-red-400 transition-all"
                            data-tmdb-id="{{ $item->tmdb_id }}">
                            Remove
                        </button>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- Sticky bar --}}
@if($items->isNotEmpty())
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 px-4 z-40 sticky-bar-safe">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
        <div class="flex gap-1 bg-white/5 p-1 rounded-lg flex-shrink-0">
            <button class="watchlist-filter active text-xs px-3 py-1.5 rounded-md transition-all" data-filter="all">All</button>
            <button class="watchlist-filter text-xs px-3 py-1.5 rounded-md transition-all text-gray-400" data-filter="saved">To Watch</button>
            <button class="watchlist-filter text-xs px-3 py-1.5 rounded-md transition-all text-gray-400" data-filter="watched">Watched</button>
        </div>
        <button id="watchlist-roll" class="btn-accent px-6">Roll</button>
    </div>
</div>
@endif

@endsection