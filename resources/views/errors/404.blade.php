@extends('layouts.app')
@section('page_title')
{{ 'OOPS - MoviePicker' }}
@endsection
@section('content')
<div class="container">
    <div class="row" style="padding-top: 80px;">
        <h3 class="w-100 text-center mb-4">Couldn't find any matching results</h3>
        <div class="m-auto text-center">
            <a href="/criteria" class="btn btn-xl btn-secondary d-flex flex-column mb-4">Movie Preference</a>
            <a href="/movie?i=new" class="btn btn-xl btn-secondary d-flex flex-column">Random Movie</a>
        </div>
    </div>
</div>
@endsection
