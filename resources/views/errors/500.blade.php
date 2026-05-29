@extends('layouts.app')
@section('page_title', '500 — MoviePickr')
@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="text-center">
        <p class="text-8xl font-bold text-accent mb-4">500</p>
        <h1 class="text-2xl font-bold text-white mb-2">Something went wrong</h1>
        <p class="text-gray-500 mb-8">A server error occurred. Try again in a moment.</p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="/movie?i=new" class="btn-accent">Random Movie</a>
            <a href="/criteria" class="btn-secondary">Set Filters</a>
        </div>
    </div>
</div>
@endsection
