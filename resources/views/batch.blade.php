@extends('layouts.app')
@section('page_title', $title ?? ($mediaType === 'tv' ? 'TV Batch — MoviePickr' : 'Batch — MoviePickr'))
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
    </div>

    {{-- Carousel --}}
    @include('includes.carousel', array_merge(
        ['allMovies' => $movies, 'name' => 'swiper-multiple', 'genres' => $movie_genres, 'showScore' => true, 'showSave' => true, 'savedIds' => $savedIds ?? []],
        $isTv ? ['linkBase' => 'tv', 'mediaType' => 'tv'] : []
    ))

</div>

{{-- Sticky bottom bar --}}
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 px-4 z-40 sticky-bar-safe">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
        <div class="flex-shrink-0"></div>
        <div class="flex items-center gap-3">
            @if(isset($providersArray) && isset($all_genres))
                <button type="button" class="btn-secondary flex-1 sm:flex-none" data-modal-open="modal-form">Criteria</button>
            @endif
            <a href="{{ Request::url() }}" class="btn-secondary flex-1 sm:flex-none text-center long-single">New Batch</a>
            <button id="batch-roll-btn" class="btn-accent flex-1 sm:flex-none">Roll</button>
        </div>
    </div>
</div>

@endsection
