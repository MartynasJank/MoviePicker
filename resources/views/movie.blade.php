@extends('layouts.app')
@section('page_title')
{{ $tmdbInfo->title.' - MoviePicker' ?? $omdbInfo->Title.' - MoviePicker' }}
@endsection
@section('scripts')
<script src="/js/showmore.js"></script>
<script src="/js/customOwlCarousel.js"></script>
<script src="/js/customModal.js"></script>
@endsection
@section('content')
<div class="container" style="padding-top: 80px;">
    @if (isset($trailer))
    <!-- TRAILER MODAL -->
        @include('includes.modal')
    @endif
    <!-- Title and release year -->
    <div class="row mb-2">
        <div class="align-bottom">
            <h1 class="d-block">{{ $tmdbInfo->title ?? $omdbInfo->Title  }}</h1>
            <span>{{ $omdbInfo->Year ?? date('Y', strtotime($tmdbInfo->release_date)) }}</span>
        </div>
    </div>
    <!-- Movie genres and rating -->
    <div class="row mb-2">
        <span>{{ $genres ?? $omdbInfo->Genre }} | Rated: {{ $omdbInfo->Rated ?? 'No info' }}</span>
    </div>
    <!-- Small screen button -->
    <div class="row mb-4">
        <div class="d-block d-md-none col-md-12">
            <a href="/movie" class="btn btn-lg btn-block btn-custom">Pick Another Movie</a>
        </div>
    </div>
    <!-- Movie poster, movie scores and plot -->
    <div class="row">
         <!-- Poster -->
        @if ($tmdbInfo->poster_path != null)
            <div class="col-md-3 mb-4">
                <img src="{{ 'https://image.tmdb.org/t/p/original/'.$tmdbInfo->poster_path }}" class="img-fluid">
            </div>
        @elseif ( isset($omdbInfo->Poster) and $omdbInfo->Poster != 'N/A' and @getimagesize($omdbInfo->Poster) )
            <div class="col-md-3 mb-4">
                <img src="{{ $omdbInfo->Poster }}" class="img-fluid">
            </div>
        @else
            <div class="col-md-3 mb-4">
                <div class="poster-img poster-placeholder text-center">
                    <h4 class="mx-4">Movie poster was not found :(</h4>
                </div>
            </div>
        @endif
        <!-- IMDB, Tomatoes and metascore also links to their page -->
        <div class="col-md-9">
            @if(isset($omdbInfo->Ratings))
                <ul class="list-group">
                    @foreach ($omdbInfo->Ratings as $rating)
                        <a
                            class="score"
                            href="{{ $urls[$rating->Source] }}"
                            @if ($urls[$rating->Source] != '#')
                                target="_blank"
                            @endif >
                                <li class="list-group-item list-group-item-action mb-3">{{ $rating->Source }}: {{ $rating->Value }}</li>
                        </a>
                    @endforeach
                </ul>
            @endif
            <!-- Plot -->
            <div>
                <h3>Plot</h3>
                <hr class="orange">
                <p class="text-md">{{ ($omdbInfo->Plot ?? $tmdbInfo->overview) ?? "No Info" }}</p>
            </div>
        </div>
    </div>
    <!-- Trailer Button -->
    <div class="row mb-4">
        <div class="col-md-3  ">
        <button id="modal-btn" type="button" class="btn btn-lg btn-block btn-custom" data-toggle="modal" data-target="#myModal">
            <span>{{ isset($trailer) ? 'Trailer' : 'No Trailer :(' }}</span>
        </button>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-3 ">
            <div class="d-none d-md-block">
                <a href="/movie" class="btn btn-lg btn-block btn-custom">Pick Another Movie</a>
            </div>
        </div>
    </div>
    <!-- General Movie Info -->
    <div class="row">
        <!-- Cast -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 card-bg">
                <div class="card-body">
                    <h3 class="card-title">Cast</h3>
                    @if (!empty($tmdbInfo->credits->cast))
                    <ul class="cast-list card-text">
                        @foreach ($tmdbInfo->credits->cast as $member)
                        <li class="cast-item">{{ $member->name }}<span class="font-weight-bold">{{ $member->character != '' ? ' as '.$member->character : '' }}</span></li>
                        @endforeach
                    </ul>
                    @else
                        <span>No Info</span>
                    @endif
                </div>
            </div>
        </div>
        <!-- Crew -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 card-bg">
                <div class="card-body">
                    <h3>Crew</h3>
                    @if (!empty($tmdbInfo->credits->crew))
                    <ul class="crew-list">
                        @foreach ($tmdbInfo->credits->crew as $member)
                        <li class="crew-item">{{ $member->job }}: <span class="font-weight-bold">{{ $member->name }}</span></li>
                        @endforeach
                    </ul>
                    @else
                        <span>No Info</span>
                    @endif
                </div>
            </div>
        </div>
        <!-- Production Companies -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 card-bg">
                <div class="card-body">
                    <h3>Production Companies</h3>
                    @if (!empty($tmdbInfo->production_companies))
                    <ul class="production-list">
                        @foreach ($tmdbInfo->production_companies as $company)
                        <li class="production-item">
                            <span class="font-weight-bold">{{ $company->name }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @else
                        <span>No Info</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <!-- Movie Info -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 card-bg">
                <div class="card-body">
                <h3>General movie info</h3>
                <ul>
                    <li>Budget: <span class="font-weight-bold">{{ $tmdbInfo->budget  == 0 ? 'No Info' : '$'.$tmdbInfo->budget }}</span></li>
                    <li>Revenue: <span class="font-weight-bold">{{ $tmdbInfo->revenue == 0 ? 'No Info' : '$'.$tmdbInfo->revenue }}</span></li>
                    <li>Runtime: <span class="font-weight-bold">{{  $omdbInfo->Runtime ?? ($tmdbInfo->runtime == 0 ? 'No Info' : $tmdbInfo->runtime.' min')   }}</span></li>
                    <li>IMDB Votes: <span class="font-weight-bold">{{ $omdbInfo->imdbVotes ?? 'No Info' }}</span></li>
                    <li>TMDB Votes: <span class="font-weight-bold">{{ $tmdbInfo->vote_count ?? 'No Info' }}</span></li>
                    <li>TMDB Score: <span class="font-weight-bold">{{ $tmdbInfo->vote_average ?? 'No Info' }}</span></li>
                    <li>Awards: <span class="font-weight-bold">{{ $omdbInfo->Awards ?? 'No Info' }}</span></li>
                </ul>
                </div>
            </div>
        </div>
        <!-- Spoken Languages -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 card-bg">
                <div class="card-body">
                    <h3>Spoken Languages</h3>
                    <ul>
                        @if (!empty($tmdbInfo->spoken_languages))
                            @foreach ($tmdbInfo->spoken_languages as $language)
                            <li>
                                <span class="font-weight-bold">{{ $language->name }}</span>
                            </li>
                            @endforeach
                        @else
                            <li>No Info</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
        <!-- Production companies -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 card-bg">
                <div class="card-body">
                    <h3>Production Countries</h3>
                    <ul>
                        @if (!empty($tmdbInfo->production_countries))
                            @foreach ($tmdbInfo->production_countries as $country)
                            <li>
                                <span class="font-weight-bold">{{ $country->name }}</span>
                            </li>
                            @endforeach
                        @else
                            <li>No Info</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Choose another movie button -->
    <div class="row mb-4">
        <a href="/movie" class="btn btn-custom btn-lg btn-block">Pick Another Movie</a>
    </div>

    <!-- Similar movies -->
    @if ($similarMovies != null)
    <div class="row">
        <div class="col-md-12 py-3">
            <h3 class="text-center w-100 mb-3">Similar movies</h3>
            @include('includes.carousel', ['allMovies' => $similarMovies, 'name' => 'owl-similar'])
        </div>
    </div>
    @endif
</div>
@endsection
