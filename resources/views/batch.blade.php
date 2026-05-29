@extends('layouts.app')
@section('page_title', $title ?? (($mediaType ?? 'movie') === 'tv' ? 'TV Batch — MoviePickr' : 'Batch — MoviePickr'))
@section('footer_pb', 'pb-32')
@section('scripts')
    @vite(['resources/js/custom/carousel.js', 'resources/js/custom/trailerModal.js', 'resources/js/custom/criteriaForm.js', 'resources/js/custom/watchlist.js'])
@endsection
@section('content')
@php $isTv = ($mediaType ?? 'movie') === 'tv'; @endphp
<div class="max-w-7xl mx-auto px-4 py-8 sm:pb-20 batch-wrapper">

    @if(isset($providersArray) && isset($all_genres))
        @include($isTv ? 'tv.criteria-modal' : 'includes.criteria-modal')
    @endif

    {{-- Header row --}}
    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $tag ?? ($isTv ? 'TV Batch' : 'Movie Batch') }}</h1>
        </div>
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
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 px-4 z-40 sticky-bar-safe">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
        <div class="flex-shrink-0">
            @if(isset($shareToken))
            <button type="button" class="btn-secondary text-sm"
                data-share
                data-share-url="{{ url('/batch/share/'.$shareToken) }}"
                data-share-title="{{ $tag ?? 'Shared Batch' }} — MoviePickr"
                title="Share this batch">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
            </button>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if(isset($providersArray) && isset($all_genres) && empty($isShared))
                <button type="button" class="btn-secondary flex-1 sm:flex-none" data-modal-open="modal-form">Filters</button>
            @endif
            @if(empty($isShared))
                <a href="{{ Request::url() }}" class="btn-secondary flex-none text-center long-single">New Batch</a>
                <button id="batch-roll-btn" class="btn-accent flex-1 sm:flex-none" data-media-type="{{ $isTv ? 'tv' : 'movie' }}">Roll</button>
            @endif
        </div>
    </div>
</div>

@endsection
