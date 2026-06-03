@extends('layouts.app')
@section('page_title', 'Movie Filters — MoviePickr')
@section('meta_description', 'Find your next movie with filters for genre, streaming service, rating, year range, and cast. Hit roll and get an instant pick.')
@section('footer_pb', 'pb-24')
@section('scripts')
    <script>window._criteriaInput = @json($userInput ?? []);</script>
    @vite(['resources/js/custom/criteriaForm.js'])
@endsection
@section('content')
<div class="max-w-2xl mx-auto px-4 py-10 pb-24">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">Movie Filters</h1>
        <p class="text-gray-500 text-sm mt-1">Filter by genre, streaming service, rating, year range, or cast. Combine as many as you like and hit Roll.</p>
    </div>

    @php $ui = $userInput ?? []; @endphp
    <form method="POST" autocomplete="off" action="/movie" id="criteria">
        @csrf
        <div class="flex flex-col gap-4">

            {{-- Years --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Release Year</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">From</label>
                        <input type="text"
                            class="input-dark bg-input {{ $errors->has('primary_release_date_gte') ? 'border-danger' : '' }}"
                            name="primary_release_date_gte"
                            placeholder="1874"
                            value="{{ old('primary_release_date_gte', $ui['primary_release_date_gte'] ?? '') }}">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">To</label>
                        <input type="text"
                            class="input-dark bg-input {{ $errors->has('primary_release_date_lte') ? 'border-danger' : '' }}"
                            name="primary_release_date_lte"
                            placeholder="{{ date('Y') }}"
                            value="{{ old('primary_release_date_lte', $ui['primary_release_date_lte'] ?? '') }}">
                    </div>
                </div>
            </div>

            {{-- Genres --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Genres</h3>
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Include</label>
                        <select name="with_genres[]" id="with_genres" multiple>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}"
                                    {{ in_array($genre->id, (array)old('with_genres', $ui['with_genres'] ?? [])) ? 'selected' : '' }}>
                                    {{ $genre->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Exclude</label>
                        <select name="without_genres[]" id="without_genres" multiple>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}"
                                    {{ in_array($genre->id, (array)old('without_genres', $ui['without_genres'] ?? [])) ? 'selected' : '' }}>
                                    {{ $genre->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Language --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Language</h3>
                @include('includes.languages', ['selectedLang' => old('with_original_language', $ui['with_original_language'] ?? '')])
            </div>

            {{-- Origin Country --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Origin Country</h3>
                @include('includes.origin-country', ['selectedCountry' => old('with_origin_country', $ui['with_origin_country'] ?? '')])
            </div>

            {{-- Streaming --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Streaming</h3>
                <select id="with_watch_providers" name="with_watch_providers[]" multiple>
                    @foreach($providersArray as $value)
                        <option value="{{ $value['id'] }}" data-logo="{{ $value['logo'] }}"
                            {{ in_array($value['id'], (array)old('with_watch_providers', $ui['with_watch_providers'] ?? [])) ? 'selected' : '' }}>
                            {{ $value['name'] }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-600 mt-1">Shows services available in your region</p>
            </div>

            {{-- People --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">People</h3>
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Actors</label>
                        <select id="with_cast" name="with_cast[]" multiple></select>
                        <p class="text-xs text-gray-600 mt-1">Type to search, you can add multiple</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Crew</label>
                        <select id="with_crew" name="with_crew[]" multiple></select>
                        <p class="text-xs text-gray-600 mt-1">Directors, writers, producers — you can add multiple</p>
                    </div>
                </div>
            </div>

            {{-- Scores --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Scores</h3>
                <div class="flex flex-col gap-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Min Score</label>
                            <input type="text"
                                class="input-dark bg-input {{ $errors->has('vote_average_gte') ? 'border-danger' : '' }}"
                                name="vote_average_gte" placeholder="0" value="{{ old('vote_average_gte', $ui['vote_average_gte'] ?? '') }}">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Max Score</label>
                            <input type="text"
                                class="input-dark bg-input {{ $errors->has('vote_average_lte') ? 'border-danger' : '' }}"
                                name="vote_average_lte" placeholder="10" value="{{ old('vote_average_lte', $ui['vote_average_lte'] ?? '') }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Min Vote Count</label>
                        <input type="text"
                            class="input-dark bg-input {{ $errors->has('vote_count_gte') ? 'border-danger' : '' }}"
                            name="vote_count_gte" placeholder="10" value="{{ old('vote_count_gte', $ui['vote_count_gte'] ?? '') }}">
                        <p class="text-xs text-gray-600 mt-1">Filters out obscure untested films</p>
                    </div>
                </div>
            </div>

            @include('errors.error')

        </div>
    </form>
</div>

{{-- Sticky bottom bar --}}
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 z-40 sticky-bar-safe">

    {{-- Mobile --}}
    <div class="md:hidden flex px-3 py-1.5 gap-2">
        <button type="button" id="btn-reset-mobile" class="btn-nav-tab">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Reset
        </button>
        <button type="submit" form="criteria" formaction="/multiple" class="btn-nav-tab">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Multiple
        </button>
        <button type="submit" form="criteria" formaction="/movie" class="btn-nav-tab accent">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>
            Find Movie
        </button>
    </div>

    {{-- Desktop --}}
    <div class="hidden md:flex max-w-2xl mx-auto px-4 py-3 items-center justify-between gap-2">
        <div class="flex items-center gap-2 ml-auto">
            <button type="submit" form="criteria" formaction="/multiple" class="btn-secondary long-single flex-none text-center">Multiple</button>
            <button type="submit" form="criteria" formaction="/movie" class="btn-accent long-single flex-none text-center">Find Movie</button>
        </div>
    </div>

</div>

@endsection
