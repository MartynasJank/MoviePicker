@extends('layouts.app')
@section('page_title', 'My Watchlist — MoviePickr')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <h1 class="text-2xl font-bold text-white">My Watchlist</h1>
        @if($items->isNotEmpty())
            <div class="flex items-center gap-2 flex-wrap">
                @if($genres->isNotEmpty())
                    <select id="genre-filter" class="input-dark text-xs py-1.5 pr-8">
                        <option value="">All genres</option>
                        @foreach($genres as $genre)
                            <option value="{{ $genre }}">{{ $genre }}</option>
                        @endforeach
                    </select>
                @endif
                <div class="flex gap-1 bg-white/5 p-1 rounded-lg">
                    <button class="watchlist-filter active text-xs px-3 py-1.5 rounded-md transition-all" data-filter="all">All</button>
                    <button class="watchlist-filter text-xs px-3 py-1.5 rounded-md transition-all text-gray-400" data-filter="saved">To Watch</button>
                    <button class="watchlist-filter text-xs px-3 py-1.5 rounded-md transition-all text-gray-400" data-filter="watched">Watched</button>
                </div>
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
                <div class="watchlist-card" data-status="{{ $item->status }}" data-genres="{{ $item->genres }}">

                    {{-- Poster --}}
                    <a href="{{ url('movie/'.$item->tmdb_id) }}" class="block group">
                        <div class="aspect-[2/3] rounded-xl overflow-hidden relative bg-white/[0.03]">
                            @if($item->poster_path)
                                <img src="https://image.tmdb.org/t/p/w300{{ $item->poster_path }}"
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
@endsection