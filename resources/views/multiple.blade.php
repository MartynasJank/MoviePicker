@extends('layouts.app')
@section('page_title', $title ?? 'Batch — MoviePickr')
@section('scripts')
    @vite(['resources/js/custom/customSwiper.js', 'resources/js/custom/customModal.js'])
@endsection
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    @if(isset($providersArray) && isset($all_genres))
        @include('includes.modal-form')
    @endif

    {{-- Header row --}}
    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $tag ?? 'Movie Batch' }}</h1>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ Request::url() }}" class="btn-accent">New Batch</a>
            @if(isset($providersArray) && isset($all_genres))
                <button type="button" class="btn-secondary" data-modal-open="modal-form">Adjust Criteria</button>
            @endif
        </div>
    </div>

    {{-- Carousel --}}
    @include('includes.carousel', ['allMovies' => $movies, 'name' => 'swiper-multiple', 'genres' => $movie_genres])

</div>
@endsection
