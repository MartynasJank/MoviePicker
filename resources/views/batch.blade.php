@extends('layouts.app')
@section('page_title', $title ?? 'Batch — MoviePickr')
@section('footer_pb', 'pb-20')
@section('scripts')
    @vite(['resources/js/custom/carousel.js', 'resources/js/custom/trailerModal.js', 'resources/js/custom/criteriaForm.js'])
@endsection
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 sm:pb-20 batch-wrapper">

    @if(isset($providersArray) && isset($all_genres))
        @include('includes.criteria-modal')
    @endif

    {{-- Header row --}}
    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $tag ?? 'Movie Batch' }}</h1>
        </div>
    </div>

    {{-- Carousel --}}
    @include('includes.carousel', ['allMovies' => $movies, 'name' => 'swiper-multiple', 'genres' => $movie_genres])

</div>

{{-- Sticky bottom bar --}}
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 px-4 z-40 sticky-bar-safe">
    <div class="max-w-7xl mx-auto flex gap-3 sm:justify-end">
        @if(isset($providersArray) && isset($all_genres))
            <button type="button" class="btn-secondary flex-1 sm:flex-none" data-modal-open="modal-form">Adjust Criteria</button>
        @endif
        <a href="{{ Request::url() }}" class="btn-accent flex-1 sm:flex-none text-center">New Batch</a>
    </div>
</div>

@endsection
