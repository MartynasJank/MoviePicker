@extends('layouts.app')
@section('page_title', '404 — MoviePickr')
@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <p class="text-8xl font-bold text-accent mb-4">404</p>
        <h1 class="text-2xl font-bold text-white mb-2">Page not found</h1>
        <p class="text-gray-500 mb-8">That movie, show, or person doesn't seem to exist — or the link is broken.</p>
        <div class="flex flex-wrap gap-3 justify-center mb-4">
            <a href="/movie?i=new" class="btn-accent">Random Movie</a>
            <a href="/tv/pick?i=new" class="btn-accent">Random TV Show</a>
        </div>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="/criteria" class="btn-secondary">Movie Criteria</a>
            <a href="/tv/criteria" class="btn-secondary">TV Criteria</a>
            <a href="/" class="btn-secondary">Home</a>
        </div>
    </div>
</div>
@endsection
