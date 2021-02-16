@extends('layouts.app')
@section('page_title')
    Batch - MoviePicker
@endsection
@section('scripts')
    <script src="/js/customOwlCarousel.js"></script>
    <script src="/js/customModal.js"></script>
    <script>
        console.log('Total pages: {{$testInfo['total']}}');
        console.log('Current page: {{$testInfo['current']}}');
    </script>
@endsection
@section('content')
    <div class="container content" style="padding-top: 80px;">
        @include('includes.modal-form')
        <div class="row py-2 mb-2 justify-content-md-center">
            {{-- New batch button --}}
            <div class="col-md-4">
                <a href="/multiple" class="btn btn-lg btn-block btn-custom">Get a new batch</a>
            </div>
            <div class="col-md-4 d-none d-lg-block d-xl-block">
                <button id="modal-btn" type="button" class="btn btn-lg btn-block btn-custom" data-toggle="modal" data-target="#modal-form">
                    <span>Adjust Form</span>
                </button>
            </div>
        </div>
        {{-- Movies --}}
        <div class="row justify-content-center">
            <div class="container">
                    <h2 class="text-center mt-0">
                        @if(count($movies->results) > 1)
                           Movies picked for you
                        @else
                            1 Movie picked for you
                        @endif
                    </h2>
                    <hr class="divider my-4"/>
                    @include('includes.carousel', ['allMovies' => $movies, 'name' => 'owl-multiple', 'genres' => $movie_genres])
            </div>
        </div>
        <div class="row mb-4 py-4 justify-content-md-center">
            {{-- Adjust form button --}}
            <div class="col-md-4 d-lg-none d-xl-none">
                <button id="modal-btn" type="button" class="btn btn-lg btn-block btn-custom" data-toggle="modal" data-target="#modal-form">
                    <span>Adjust Form</span>
                </button>
            </div>
        </div>
    </div>
@endsection
