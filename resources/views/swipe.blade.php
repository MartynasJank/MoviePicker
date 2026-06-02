@extends('layouts.app')
@section('page_title', 'Movie Swipe — MoviePickr')
@section('scripts')
    @vite(['resources/js/custom/criteriaForm.js', 'resources/js/custom/swipe.js'])
@endsection

@section('content')
</div>{{-- close layout container --}}
<div class="fixed top-16 left-0 right-0 bottom-0 flex flex-col bg-[#0f0f0f]" id="swipe-root">

    {{-- Card area --}}
    <div class="flex-1 flex items-center justify-center overflow-hidden min-h-0 px-4 py-2" id="card-area">
        <div id="overlay-like" class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-0 z-20">
            <span class="text-5xl font-black text-green-400 border-4 border-green-400 rounded-xl px-5 py-2 rotate-[-12deg]">LIKE</span>
        </div>
        <div id="overlay-skip" class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-0 z-20">
            <span class="text-5xl font-black text-red-400 border-4 border-red-400 rounded-xl px-5 py-2 rotate-[12deg]">SKIP</span>
        </div>
        {{-- aspect-[2/3] keeps poster ratio, h-full fills available height, w-auto derives width --}}
        <div id="card-stack" class="relative h-full w-full" style="max-width:min(100%, calc((100vh - 160px) * 2/3))"></div>
    </div>

    @if($isAdmin)
    <div class="flex-shrink-0 text-center pb-1 debug-only" id="swipe-counter-wrap">
        <span id="swipe-counter" class="text-xs text-gray-600"></span>
    </div>
    @endif

    {{-- Action buttons --}}
    <div class="flex items-center justify-center gap-6 pt-1 pb-4 flex-shrink-0">
        <button id="btn-skip" class="w-14 h-14 rounded-full bg-white/10 flex items-center justify-center text-red-400 hover:bg-red-500/20 transition-all active:scale-90">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <button id="btn-undo" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-gray-500 hover:text-white transition-all active:scale-90" disabled>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10h10a8 8 0 018 8v2M3 10l6 6M3 10l6-6"/></svg>
        </button>
        <button id="btn-criteria" data-modal-open="modal-form" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-all active:scale-90">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
        <button id="swipe-end-btn" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-all active:scale-90 relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M8 4v16M16 4v16M2 12h20M2 8h4M2 16h4M18 8h4M18 16h4"/></svg>
            <span id="liked-badge" class="hidden absolute -top-1 -right-1 bg-accent text-white text-[9px] font-bold rounded-full w-4 h-4 flex items-center justify-center"></span>
        </button>
        <button id="btn-like" class="w-14 h-14 rounded-full bg-green-500/20 flex items-center justify-center text-green-400 hover:bg-green-500/30 transition-all active:scale-90">
            <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        </button>
    </div>
</div>

{{-- Full criteria modal (action overridden by JS) --}}
@include('includes.criteria-modal')

{{-- Results overlay --}}
<div id="results-overlay" class="hidden fixed inset-0 z-50 bg-[#0f0f0f] flex flex-col">
    {{-- Sticky header --}}
    <div class="flex-shrink-0 bg-[#0f0f0f]/95 backdrop-blur-sm flex items-center justify-between px-4 pt-4 pb-3 border-b border-white/10">
        <button id="results-close" class="text-gray-400 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </button>
        <h2 class="text-base font-bold text-white">Movies you liked</h2>
        <div class="w-8"></div>
    </div>
    {{-- Scrollable grid --}}
    <div class="flex-1 overflow-y-auto min-h-0">
        <div id="results-grid" class="p-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3"></div>
    </div>
    {{-- Sticky footer --}}
    <div class="flex-shrink-0 p-4 flex gap-3 border-t border-white/10 bg-[#0f0f0f]">
        <button id="results-continue" class="btn-accent flex-1 py-3">Keep Swiping</button>
        <button id="results-end" class="flex-1 py-3 rounded-xl font-semibold text-sm bg-red-600/20 text-red-400 hover:bg-red-600/30 transition-colors">End Session</button>
    </div>
</div>

<script>
window.swipeMovies      = @json($movies);
window.swipePage        = {{ $initialPage }};
window.swipeLoggedIn    = {{ $isLoggedIn ? 'true' : 'false' }};
window.swipeTotalResults = {{ $totalResults }};
window.swipeWatchlistIds = @json($watchlistIds);
</script>
@endsection
