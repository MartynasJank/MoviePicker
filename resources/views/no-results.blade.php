@extends('layouts.app')
@section('page_title', 'No Results — MoviePickr')
@section('content')
<div class="max-w-lg mx-auto px-4 py-20 text-center">

    <div class="text-5xl mb-6">🎲</div>

    <h1 class="text-2xl font-bold text-white mb-3">No results found</h1>
    <p class="text-gray-500 text-sm mb-10">
        Nothing matched your current filters. Try loosening the criteria, roll something completely random, or browse our curated roulettes.
    </p>

    <div class="flex flex-col gap-3">

        {{-- Roll again with same criteria but a different page --}}
        <a href="{{ $rollAgainUrl }}" class="btn-accent py-3 text-center">
            Roll Again
        </a>

        {{-- Adjust criteria --}}
        <a href="{{ $criteriaUrl }}" class="btn-secondary py-3 text-center">
            Adjust Criteria
        </a>

        {{-- Random — clears criteria and rolls with no filters --}}
        <a href="{{ $randomUrl }}" class="btn-secondary py-3 text-center">
            Roll Something Random
        </a>

        {{-- Roulettes --}}
        <a href="/roulettes" class="btn-secondary py-3 text-center">
            Browse Roulettes
        </a>

    </div>
</div>
@endsection
