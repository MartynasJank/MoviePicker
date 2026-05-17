@extends('layouts.app')
@section('page_title', $title ?? 'Batch — MoviePickr')
@section('scripts')
    @vite(['resources/js/custom/carousel.js', 'resources/js/custom/trailerModal.js', 'resources/js/custom/criteriaForm.js'])
@endsection
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 pb-24 sm:pb-8">

    @if(isset($providersArray) && isset($all_genres))
        @include('includes.criteria-modal')
    @endif

    {{-- Header row --}}
    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $tag ?? 'Movie Batch' }}</h1>
        </div>
        <div class="hidden sm:flex gap-2 flex-wrap">
            <a href="{{ Request::url() }}" class="btn-accent">New Batch</a>
            @if(isset($providersArray) && isset($all_genres))
                <button type="button" class="btn-secondary" data-modal-open="modal-form">Adjust Criteria</button>
            @endif
        </div>
    </div>

    {{-- Carousel --}}
    @include('includes.carousel', ['allMovies' => $movies, 'name' => 'swiper-multiple', 'genres' => $movie_genres])

</div>

{{-- Mobile sticky bottom bar --}}
<div class="fixed bottom-0 left-0 right-0 sm:hidden bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 px-4 py-3 z-40">
    <div class="flex gap-3">
        <a href="{{ Request::url() }}" class="btn-accent flex-1 text-center">New Batch</a>
        @if(isset($providersArray) && isset($all_genres))
            <button type="button" class="btn-secondary flex-1" data-modal-open="modal-form">Adjust</button>
        @endif
    </div>
</div>

@endsection
