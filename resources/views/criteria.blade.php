@extends('layouts.app')

@section('page_title')
    {{ ' Criteria - MoviePicker' ?? $omdbInfo->Title.' - MoviePicker' }}
@endsection

@section('scripts')
    <script src="/js/customForm.js"></script>
@endsection

@section('content')
<div class="container content" style="padding-top: 80px;">
    <form method="POST" autocomplete="off" action="/movie" class="custom-border">
        @csrf
        <div id="smartwizard" style="display: none">
            <ul>
                <li><a href="#step-1">Years</a></li>
                <li><a href="#step-2">Genres</a></li>
                <li><a href="#step-3">Language</a></li>
                <li><a href="#step-4">People</a></li>
                <li><a href="#step-5">Scores</a></li>
            </ul>
            <div>
                <div id="step-1" class="">
                    <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="primary_release_date_gte">Start Year</label>
                        <input type="text" class="form-control bg-input movie-input border {{ ($errors->has('primary_release_date_gte')) ?  'border-danger' :  ''}}" id="primary_release_date_gte" name="primary_release_date_gte" placeholder="1874" value="{{ old('primary_release_date_gte') }}">
                        <small id="primary_release_date_gte" class="form-text text-muted">Oldest movie in databse was released in 1874</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="primary_release_date_lte">End Year</label>
                        <input type="text" class="form-control bg-input movie-input border {{ ($errors->has('primary_release_date_lte')) ?  'border-danger' :  ''}}" id="primary_release_date_lte" name="primary_release_date_lte" placeholder="2020" value="{{ old('primary_release_date_lte') }}">
                        <small id="primary_release_date_lte" class="form-text text-muted">Default: Current year</small>
                    </div>
                    </div>
                </div>
                <div id="step-2" class="">
                    <div class="form-group">
                        <label for="with_genres">Genres</label>
                        <select
                            name="with_genres[]"
                            id="with_genres"
                            class="selectpicker form-control bg-input"
                            data-actions-box="true"
                            data-style="btn btn-white"
                            title="Nothing selected"
                            multiple>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                            @endforeach
                        </select>
                        <small id="genres" class="form-text text-muted">Default: All | Select multiple</small>
                    </div>
                    <div class="form-group">
                        <label for="with_genres">Without Genres</label>
                        <select
                            name="without_genres[]"
                            id="without_genres"
                            class="selectpicker form-control bg-input"
                            data-actions-box="true"
                            data-style="btn btn-white"
                            title="Nothing selected"
                            multiple>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                            @endforeach
                        </select>
                        <small id="genres" class="form-text text-muted">Default: none | Select multiple</small>
                    </div>
                </div>
                <div id="step-3">
                        @include('includes.languages')
                </div>
                <div id="step-4">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                        <label for="with_cast">Actor</label>
                        <input
                            placeholder="Name"
                            type="text"
                            class="cast"
                            multiple="multiple"
                            name="with_cast"
                            id="with_cast">
                            <small id="with_cast" class="form-text text-muted">Names of actors</small>
                        </div>
                        <div class="form-group col-md-6">
                        <label for="with_crew">Crew</label>
                        <input
                            placeholder="Name"
                            type="text"
                            class="crew"
                            multiple="multiple"
                            name="with_crew"
                            id="with_crew">
                            <small id="with_crew" class="form-text text-muted">Names of Producers, writers, directors, etc.</small>
                        </div>
                    </div>
                </div>
                <div id="step-5">
                    <div class="form-row">
                        <div class="form-group col-md-6 scores">
                            <label for="vote_average_gte">Lowest score</label>
                            <input type="text" class="form-control bg-input movie-input border {{ ($errors->has('vote_average_gte')) ?  'border-danger' :  ''}}" id="vote_average_gte" name="vote_average_gte" placeholder="0" value="{{ old('vote_average_gte') }}">
                            <small id="vote_average_gte" class="form-text text-muted">Default: 0</small>
                        </div>
                        <div class="form-group col-md-6 scores">
                            <label for="vote_average_lte">Highest score</label>
                            <input type="text" class="form-control bg-input movie-input border {{ ($errors->has('vote_average_lte')) ?  'border-danger' :  ''}}" id="vote_average_lte" name="vote_average_lte" placeholder="10" value="{{ old('vote_average_lte') }}">
                            <small id="vote_average_lte" class="form-text text-muted">Default: 10</small>
                        </div>
                    </div>
                    <div class="row">
                        <label for="vote_count_gte">Minimum vote count</label>
                        <input type="text" class="form-control bg-input movie-input border {{ ($errors->has('vote_count_gte')) ?  'border-danger' :  ''}}" id="vote_count_gte" name="vote_count_gte" placeholder="10" value="{{ old('vote_count_gte') }}">
                        <small id="vote_count_gte" class="form-text text-muted">Default: 10 (vote count is from TMDB)</small>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @include('errors.error')
</div>
@endsection
