@extends('layouts.app')
@section('page_title', 'My Watchlist — MoviePickr')
@section('footer_pb', 'pb-32')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 pb-24">

    <div class="mb-4">
        {{-- Header + filter toggle --}}
        <div class="flex items-center gap-3 mb-3">
            <h1 class="text-2xl font-bold text-white flex-1">My Watchlist <span id="wl-count" class="text-gray-500 font-normal text-lg">({{ $items->count() }})</span></h1>
            @if($items->isNotEmpty())
            <button id="filter-expand-btn" class="flex-shrink-0 flex items-center gap-1.5 text-sm px-3 py-2 rounded-lg bg-white/5 text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M7 12h10M11 18h2"/></svg>
                Filters
            </button>
            @endif
        </div>

        @if($items->isNotEmpty())
        {{-- Collapsible filter panel --}}
        <div id="filter-expand-body" class="hidden flex-col gap-2">
            <div class="flex flex-wrap gap-2">
                <div class="flex gap-1 bg-white/5 p-1 rounded-lg">
                    <button class="watchlist-filter active text-xs px-3 py-1.5 rounded-md transition-all text-accent" data-filter="all">All</button>
                    <button class="watchlist-filter text-xs px-3 py-1.5 rounded-md transition-all text-gray-400" data-filter="saved">To Watch</button>
                    <button class="watchlist-filter text-xs px-3 py-1.5 rounded-md transition-all text-gray-400" data-filter="watched">Watched</button>
                </div>
                <div class="flex gap-1 bg-white/5 p-1 rounded-lg">
                    <button class="type-filter active text-xs px-3 py-1.5 rounded-md transition-all text-accent" data-type="all">All</button>
                    <button class="type-filter text-xs px-3 py-1.5 rounded-md transition-all text-gray-400" data-type="movie">Films</button>
                    <button class="type-filter text-xs px-3 py-1.5 rounded-md transition-all text-gray-400" data-type="tv">TV</button>
                </div>
                @if($items->where('source', 'swipe')->isNotEmpty())
                <div class="flex gap-1 bg-white/5 p-1 rounded-lg">
                    <button class="source-filter active text-xs px-3 py-1.5 rounded-md transition-all text-accent" data-source="all">All</button>
                    <button class="source-filter text-xs px-3 py-1.5 rounded-md transition-all text-gray-400" data-source="swipe">Swipe</button>
                    <button class="source-filter text-xs px-3 py-1.5 rounded-md transition-all text-gray-400" data-source="manual">Manual</button>
                </div>
                @endif
            </div>
            @if($genres->isNotEmpty())
                <div class="flex flex-col sm:flex-row gap-2">
                    <div class="flex-1">
                        <select id="genre-select" multiple placeholder="Filter by genre...">
                            @foreach($genres as $genre)
                                <option value="{{ $genre }}">{{ $genre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1">
                        <select id="exclude-genre-select" multiple placeholder="Exclude genre...">
                            @foreach($genres as $genre)
                                <option value="{{ $genre }}">{{ $genre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
            <select id="sort-select" class="input-dark text-sm w-full sm:w-44">
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
                     data-type="{{ $item->type ?? 'movie' }}"
                     data-genres="{{ $item->genres }}"
                     data-source="{{ $item->source }}"
                     data-date="{{ $item->created_at->timestamp }}"
                     data-title="{{ $item->title }}"
                     data-year="{{ $item->year ?? 0 }}"
                     data-rating="{{ $item->vote_average ?? 0 }}">

                    {{-- Poster --}}
                    <a href="{{ $item->type === 'tv' ? url('tv/'.$item->tmdb_id) : url('movie/'.$item->tmdb_id) }}" class="block group long-movie" data-name="{{ $item->title }}">
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

                            {{-- Type badge --}}
                            @if(($item->type ?? 'movie') === 'tv')
                                <div class="absolute top-2 right-2 bg-accent/80 text-white text-xs font-semibold px-1.5 py-0.5 rounded pointer-events-none">TV</div>
                            @else
                                <div class="absolute top-2 right-2 bg-black/50 text-white/50 text-xs font-semibold px-1.5 py-0.5 rounded pointer-events-none">Film</div>
                            @endif

                            {{-- Watched badge --}}
                            <div class="watched-overlay absolute inset-0 bg-black/50 flex items-center justify-center {{ $item->status === 'watched' ? '' : 'hidden' }} pointer-events-none">
                                <span class="bg-black/60 text-white text-xs font-medium px-3 py-1.5 rounded-full border border-white/20">✓ Watched</span>
                            </div>
                        </div>
                    </a>

                    {{-- Action buttons (icon-only) --}}
                    <div class="flex gap-1.5 mt-2">
                        <button class="toggle-watched flex-1 py-2 rounded-lg transition-all flex items-center justify-center
                            {{ $item->status === 'watched'
                                ? 'bg-white/10 text-white hover:bg-white/5 hover:text-gray-400'
                                : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white' }}"
                            data-tmdb-id="{{ $item->tmdb_id }}"
                            data-status="{{ $item->status }}"
                            title="{{ $item->status === 'watched' ? 'Mark unwatched' : 'Mark watched' }}">
                            @if($item->status === 'watched')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/></svg>
                            @endif
                        </button>
                        <button class="remove-from-watchlist py-2 px-3 rounded-lg bg-white/5 text-gray-500 hover:bg-red-900/30 hover:text-red-400 transition-all flex items-center justify-center"
                            data-tmdb-id="{{ $item->tmdb_id }}"
                            title="Remove">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- Sticky bar --}}
@if($items->isNotEmpty())
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 z-40 sticky-bar-safe">

    {{-- Mobile: 4 equal-width buttons --}}
    <div class="md:hidden flex px-3 py-2 gap-2">
        <button id="watchlist-share-m" class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl bg-white/5 text-gray-400 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
            <span class="text-[10px] font-medium">Share</span>
        </button>
        <button id="wl-swipe-btn-m" class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl bg-white/5 text-gray-400 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M7 16l-4-4m0 0l4-4m-4 4h18M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            <span class="text-[10px] font-medium">Swipe</span>
        </button>
        <button id="watchlist-collab-m" class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl bg-white/5 text-gray-400 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            <span class="text-[10px] font-medium">Collab</span>
        </button>
        <button id="watchlist-roll-m" class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl bg-accent/15 text-accent hover:bg-accent/25 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>
            <span class="text-[10px] font-semibold">Roll</span>
        </button>
    </div>

    {{-- Desktop: original layout --}}
    <div class="hidden md:flex max-w-7xl mx-auto px-4 items-center justify-between py-1">
        <div class="flex-shrink-0">
            <button id="watchlist-share" class="btn-secondary text-sm" title="Share visible list">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
            </button>
        </div>
        <div class="flex items-center gap-3">
            <button id="wl-swipe-btn" class="btn-secondary text-sm">Swipe</button>
            <button id="watchlist-collab" class="btn-secondary text-sm">Pick Together</button>
            <button id="watchlist-roll" class="btn-accent px-8">Roll</button>
        </div>
    </div>

</div>
@endif

{{-- Watchlist Swipe Mode overlay (sits below nav) --}}
<div id="wl-swipe-overlay" class="hidden fixed top-16 left-0 right-0 bottom-0 z-40 bg-[#0f0f0f] flex flex-col">

    {{-- Counter row --}}
    <div class="flex-shrink-0 flex items-center justify-between px-4 pt-2 pb-0">
        <button id="wl-swipe-close" class="p-1.5 text-gray-400 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </button>
        <span id="wl-swipe-counter" class="text-xs text-gray-500"></span>
        <div class="w-8"></div>
    </div>

    {{-- Card area --}}
    <div class="flex-1 flex items-center justify-center overflow-hidden min-h-0 px-4 py-2" id="wl-card-area">
        <div id="wl-overlay-keep" class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-0 z-20">
            <span class="text-5xl font-black text-green-400 border-4 border-green-400 rounded-xl px-5 py-2 rotate-[-12deg]">KEEP</span>
        </div>
        <div id="wl-overlay-remove" class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-0 z-20">
            <span class="text-5xl font-black text-red-400 border-4 border-red-400 rounded-xl px-5 py-2 rotate-[12deg]">REMOVE</span>
        </div>
        <div id="wl-card-stack" class="relative h-full w-full" style="max-width:min(100%, calc((100vh - 160px) * 2/3))"></div>
    </div>

    {{-- Action buttons: same layout as swipe page but without criteria/results --}}
    <div class="flex items-center justify-center gap-6 pt-1 pb-4 flex-shrink-0">
        <button id="wl-btn-remove" class="w-14 h-14 rounded-full bg-white/10 flex items-center justify-center text-red-400 hover:bg-red-500/20 transition-all active:scale-90">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <button id="wl-btn-undo" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-gray-500 hover:text-white transition-all active:scale-90" disabled>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10h10a8 8 0 018 8v2M3 10l6 6M3 10l6-6"/></svg>
        </button>
        <button id="wl-btn-keep" class="w-14 h-14 rounded-full bg-green-500/20 flex items-center justify-center text-green-400 hover:bg-green-500/30 transition-all active:scale-90">
            <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        </button>
    </div>

    {{-- Done screen --}}
    <div id="wl-swipe-done" class="hidden absolute inset-0 flex flex-col items-center justify-center gap-5 bg-[#0f0f0f] z-30 px-6 text-center">
        <div id="wl-done-confetti" class="absolute inset-0 pointer-events-none overflow-hidden"></div>
        <div id="wl-done-icon" class="text-8xl select-none">🎉</div>
        <h2 id="wl-done-title" class="text-3xl font-black text-white"></h2>
        <p id="wl-done-stats" class="text-gray-400 text-base"></p>
        <button id="wl-done-close" class="btn-accent px-10 py-3 text-base mt-2">Back to Watchlist</button>
    </div>

</div>

@endsection