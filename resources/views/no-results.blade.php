@extends('layouts.app')
@section('page_title', 'No Results — MoviePickr')
@section('scripts')
    @vite(['resources/js/custom/criteriaForm.js'])
@endsection
@section('content')
@once
<script>if (typeof gtag !== 'undefined') gtag('event', 'no_results_hit', { media_type: '{{ $isTv ? 'tv' : 'movie' }}' });</script>
@endonce

@if($isTv)
    @include('tv.criteria-modal')
@else
    @include('includes.criteria-modal')
@endif

<div class="max-w-lg mx-auto px-4 py-20 text-center">

    <div class="text-5xl mb-6">🎲</div>

    <h1 class="text-2xl font-bold text-white mb-3">No results found</h1>
    <p class="text-gray-500 text-sm mb-10">
        Nothing matched your current filters. Try adjusting your filters, roll something completely random, or browse our curated roulettes.
    </p>

    <div class="flex flex-col gap-3">

        <button type="button" class="btn-accent py-3 js-criteria-btn" data-modal-open="modal-form">
            Adjust Filters
        </button>

        <a href="{{ $randomUrl }}" class="btn-secondary py-3 text-center">
            Roll Something Random
        </a>

        <a href="/roulettes" class="btn-secondary py-3 text-center">
            Browse Roulettes
        </a>

    </div>
</div>
@endsection
