@extends('layouts.app')
@section('page_title', 'Pick Together — MoviePickr')
@section('og_title', 'Pick a Movie Together — MoviePickr')
@section('og_description', 'Veto movies until one is left. Everyone votes in real time.')
@section('footer_pb', 'pb-32')
@section('scripts')
    @vite(['resources/js/custom/collabBatch.js'])
@endsection
@section('content')

<div class="max-w-7xl mx-auto px-4 py-6"
     data-token="{{ $batch->token }}"
     data-media-type="{{ $batch->media_type }}"
     id="collab-root">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Pick Together</h1>
            <p class="text-sm text-gray-500 mt-0.5">Veto movies until one is left</p>
        </div>
        <div class="flex items-center gap-3">
            <span id="participant-count" class="text-xs text-gray-500"></span>
            <button id="collab-share-btn" class="btn-secondary text-sm flex items-center gap-1.5" title="Copy invite link">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
                Invite
            </button>
        </div>
    </div>

    {{-- Veto count bar --}}
    <div class="flex items-center gap-3 mb-5">
        <span id="remaining-count" class="text-sm text-gray-400">
            <span id="remaining-num">{{ count($batch->movies) }}</span> movies remaining
        </span>
        <div class="flex-1 h-1 bg-white/10 rounded-full overflow-hidden">
            <div id="progress-veto" class="h-full bg-accent transition-all duration-500"
                 style="width: {{ count($batch->movies) > 0 ? '100%' : '0%' }}"></div>
        </div>
    </div>

    {{-- Toast area --}}
    <div id="veto-toasts" class="fixed top-20 left-1/2 z-50 flex flex-col gap-2 pointer-events-none" style="transform:translateX(-50%)"></div>

    {{-- Movie grid --}}
    <div id="collab-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
        @foreach($batch->movies as $movie)
        @php
            $isTv    = ($batch->media_type === 'tv') || ($movie['media_type'] ?? 'movie') === 'tv';
            $itemUrl = ($isTv ? '/tv/' : '/movie/') . $movie['id'];
            $title   = $movie['title'] ?? $movie['name'] ?? '';
        @endphp
        <div class="collab-card relative group" data-id="{{ $movie['id'] }}">
            <a href="{{ $itemUrl }}" class="block long-movie" data-name="{{ $title }}">
                <div class="card overflow-hidden">
                    <div class="aspect-[2/3] bg-white/[0.03] overflow-hidden">
                        @if(!empty($movie['poster_path']))
                            <img src="https://image.tmdb.org/t/p/w342{{ $movie['poster_path'] }}"
                                 alt="{{ $title }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs px-2 text-center">{{ $title }}</div>
                        @endif
                    </div>
                    <div class="p-2">
                        <div class="text-xs font-medium text-white truncate">{{ $title }}</div>
                        @if(!empty($movie['vote_average']))
                            <div class="text-xs text-gray-500 mt-0.5">★ {{ number_format($movie['vote_average'], 1) }}</div>
                        @endif
                    </div>
                </div>
            </a>
            {{-- Veto button --}}
            <button class="veto-btn absolute top-2 right-2 w-7 h-7 rounded-full bg-black/70 border border-white/20 text-white text-sm flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-900/80 hover:border-red-700/50 z-10"
                    data-id="{{ $movie['id'] }}" title="Veto">✕</button>
        </div>
        @endforeach
    </div>

    {{-- Winner overlay (hidden until one left) --}}
    <div id="winner-overlay" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm px-4">
        <div class="text-center max-w-sm">
            <div id="winner-poster" class="mx-auto w-40 rounded-xl overflow-hidden mb-5 shadow-2xl"></div>
            <p class="text-gray-400 text-sm mb-1" id="winner-decided-by"></p>
            <h2 class="text-2xl font-bold text-white mb-2" id="winner-title"></h2>
            <p class="text-gray-400 text-sm mb-6">You all picked this one!</p>
            <a id="winner-link" href="#" class="btn-accent px-8 py-3 text-base">Watch Now</a>
        </div>
    </div>

</div>

{{-- Inject batch data for JS --}}
<script>
window.collabToken     = @json($batch->token);
window.collabMovies    = @json($batch->movies);
window.collabMediaType = @json($batch->media_type);
window.collabIdentity  = localStorage.getItem('collab_identity') || null;
</script>

@endsection
