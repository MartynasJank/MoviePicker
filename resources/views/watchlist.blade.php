@extends('layouts.app')
@section('page_title', 'My Watchlist — MoviePickr')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-white">My Watchlist</h1>
        @if($items->isNotEmpty())
            <div class="flex gap-2">
                <button class="btn-secondary text-sm watchlist-filter active" data-filter="all">All</button>
                <button class="btn-secondary text-sm watchlist-filter" data-filter="saved">To Watch</button>
                <button class="btn-secondary text-sm watchlist-filter" data-filter="watched">Watched</button>
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
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4" id="watchlist-grid">
            @foreach($items as $item)
                <div class="watchlist-card group" data-status="{{ $item->status }}" data-id="{{ $item->id }}">
                    <a href="{{ url('movie/'.$item->tmdb_id) }}" class="block">
                        <div class="card card-hover overflow-hidden">
                            <div class="aspect-[2/3] bg-white/[0.03] overflow-hidden relative">
                                @if($item->poster_path)
                                    <img src="https://image.tmdb.org/t/p/w300{{ $item->poster_path }}"
                                        alt="{{ $item->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs text-center px-3">No poster</div>
                                @endif
                                <div class="watched-overlay absolute inset-0 bg-black/50 flex items-center justify-center {{ $item->status === 'watched' ? '' : 'hidden' }}">
                                    <span class="text-white text-2xl">✓</span>
                                </div>
                            </div>
                            <div class="p-3">
                                <h4 class="text-sm font-medium text-white leading-snug line-clamp-2 group-hover:text-accent transition-colors">
                                    {{ $item->title }}
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $item->year }}
                                    @if($item->genres)
                                        · {{ $item->genres }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </a>
                    <div class="flex gap-1 mt-1">
                        <button class="flex-1 text-xs py-1.5 px-2 rounded-md bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition-all toggle-watched"
                            data-tmdb-id="{{ $item->tmdb_id }}"
                            data-status="{{ $item->status }}">
                            {{ $item->status === 'watched' ? '✓ Watched' : 'Mark watched' }}
                        </button>
                        <button class="text-xs py-1.5 px-2 rounded-md bg-white/5 hover:bg-red-900/40 text-gray-500 hover:text-red-400 transition-all remove-from-watchlist"
                            data-tmdb-id="{{ $item->tmdb_id }}">
                            ✕
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection