@extends('layouts.app')
@section('page_title', $title ?? (($mediaType ?? 'movie') === 'tv' ? 'TV Batch — MoviePickr' : 'Batch — MoviePickr'))
@if(!empty($ogTitle))
@section('og_title', $ogTitle)
@section('og_description', $ogDescription)
@if(!empty($ogImage))
@section('og_image', $ogImage)
@endif
@endif
@section('footer_pb', 'pb-32')
@section('scripts')
    @vite(['resources/js/custom/carousel.js', 'resources/js/custom/trailerModal.js', 'resources/js/custom/criteriaForm.js', 'resources/js/custom/watchlist.js'])
@endsection
@section('content')
@php $isTv = ($mediaType ?? 'movie') === 'tv'; @endphp
<script>window.batchCriteria = @json($user_input ?? []);</script>
<div class="max-w-7xl mx-auto px-4 py-8 sm:pb-20 batch-wrapper">

    @if(isset($providersArray) && isset($all_genres))
        @include($isTv ? 'tv.criteria-modal' : 'includes.criteria-modal')
    @endif

    {{-- Header row --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white mb-3">{{ $tag ?? ($isTv ? 'TV Batch' : 'Movie Batch') }}</h1>

        {{-- Mobile: btn-nav-tab row --}}
        <div class="flex gap-2 sm:hidden">
            @if(isset($shareToken) && empty($isShared))
            <button type="button" class="btn-nav-tab"
                data-share
                data-share-url="{{ url('/batch/share/'.$shareToken) }}"
                data-share-title="{{ $tag ?? 'Shared Batch' }} — MoviePickr">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
                Share
            </button>
            @endif
            @auth
            @if(isset($shareToken))
            <button type="button" id="collab-start-btn-m" class="btn-nav-tab"
                data-media-type="{{ $mediaType ?? 'movie' }}">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                Together
            </button>
            @endif
            @endauth
            @auth
            <button type="button" id="save-roulette-btn-m" class="btn-nav-tab">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                Roulette
            </button>
            @endauth
        </div>

        {{-- Desktop: text buttons --}}
        <div class="hidden sm:flex items-center gap-2">
            @if(isset($shareToken) && empty($isShared))
            <button type="button" class="btn-secondary text-sm"
                data-share
                data-share-url="{{ url('/batch/share/'.$shareToken) }}"
                data-share-title="{{ $tag ?? 'Shared Batch' }} — MoviePickr"
                title="Share this batch">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
            </button>
            @endif
            @auth
            @if(isset($shareToken))
            <button type="button" id="collab-start-btn" class="btn-secondary text-sm"
                data-media-type="{{ $mediaType ?? 'movie' }}"
                title="Pick together — veto movies in real time with friends">
                Pick Together
            </button>
            @endif
            @endauth
            @auth
            <button type="button" id="save-roulette-btn"
                    class="btn-secondary text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/>
                </svg>
                Save as Roulette
            </button>
            @endauth
        </div>
    </div>

    {{-- Save as Roulette modal --}}
    @auth
    <div id="save-roulette-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="save-roulette-backdrop"></div>
        <div class="relative bg-[#1a1a1a] border border-white/10 rounded-2xl p-6 w-full max-w-sm shadow-2xl">
            <h2 class="text-lg font-bold text-white mb-4">Save as Roulette</h2>
            <form method="POST" action="{{ route('my-roulettes.from-criteria') }}">
                @csrf
                <input type="hidden" name="media_type" value="{{ $isTv ? 'tv' : 'movie' }}">
                <div class="mb-4">
                    <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Name</label>
                    <input type="text" name="name" required maxlength="80"
                           class="input-dark w-full" placeholder="e.g. Sci-Fi on Netflix…"
                           id="save-roulette-name">
                </div>
                <div class="mb-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_public" value="1"
                               class="w-4 h-4 rounded border-white/20 bg-white/5 text-accent">
                        <span class="text-sm text-gray-300">Public — anyone with the link can roll this</span>
                    </label>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn-accent flex-1 py-2.5 text-sm">Save</button>
                    <button type="button" id="save-roulette-cancel" class="btn-secondary px-5 py-2.5 text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    (function () {
        const modal    = document.getElementById('save-roulette-modal');
        const nameInput = document.getElementById('save-roulette-name');
        document.getElementById('save-roulette-btn').addEventListener('click', function () {
            modal.classList.remove('hidden');
            nameInput.focus();
        });
        document.getElementById('save-roulette-cancel').addEventListener('click', function () {
            modal.classList.add('hidden');
        });
        document.getElementById('save-roulette-backdrop').addEventListener('click', function () {
            modal.classList.add('hidden');
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') modal.classList.add('hidden');
        });
    })();
    </script>
    @endauth

    {{-- Carousel --}}
    @include('includes.carousel', array_merge(
        ['allMovies' => $movies, 'name' => 'swiper-multiple', 'genres' => $movie_genres, 'showScore' => true, 'showSave' => true, 'savedIds' => $savedIds ?? []],
        $isTv ? ['linkBase' => 'tv', 'mediaType' => 'tv'] : []
    ))

</div>

{{-- Sticky bottom bar --}}
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 z-40 sticky-bar-safe">

    {{-- Mobile --}}
    <div class="md:hidden flex px-3 py-1.5 gap-2">
        @if(empty($isShared))
            @if(isset($providersArray) && isset($all_genres))
            <button type="button" class="btn-nav-tab" data-modal-open="modal-form">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M7 12h10M11 18h2"/></svg>
                Filters
            </button>
            @endif
            <a href="{{ Request::url() }}" class="btn-nav-tab">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                New
            </a>
            <button id="batch-roll-btn-m" class="btn-nav-tab accent" data-media-type="{{ $isTv ? 'tv' : 'movie' }}">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>
                Roll
            </button>
        @else
            <button id="shared-batch-roll-btn-m" class="btn-nav-tab accent" data-media-type="{{ $isTv ? 'tv' : 'movie' }}">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>
                Roll
            </button>
        @endif
    </div>

    {{-- Desktop --}}
    <div class="hidden md:flex max-w-7xl mx-auto px-4 items-center justify-end gap-3">
        @if(isset($providersArray) && isset($all_genres) && empty($isShared))
            <button type="button" class="btn-secondary flex-1 sm:flex-none" data-modal-open="modal-form">Filters</button>
        @endif
        @if(empty($isShared))
            <a href="{{ Request::url() }}" class="btn-secondary flex-none text-center long-single">New Batch</a>
            <button id="batch-roll-btn" class="btn-accent flex-1 sm:flex-none" data-media-type="{{ $isTv ? 'tv' : 'movie' }}">Roll</button>
        @else
            <button id="shared-batch-roll-btn" class="btn-accent flex-1 sm:flex-none" data-media-type="{{ $isTv ? 'tv' : 'movie' }}">Roll</button>
        @endif
    </div>

</div>
<script>
document.getElementById('batch-roll-btn-m')?.addEventListener('click',()=>document.getElementById('batch-roll-btn').click());
document.getElementById('shared-batch-roll-btn-m')?.addEventListener('click',()=>document.getElementById('shared-batch-roll-btn').click());
document.getElementById('collab-start-btn-m')?.addEventListener('click',()=>document.getElementById('collab-start-btn').click());
document.getElementById('save-roulette-btn-m')?.addEventListener('click',()=>document.getElementById('save-roulette-btn').click());
</script>

@endsection
