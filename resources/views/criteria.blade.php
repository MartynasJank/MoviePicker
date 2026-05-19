@extends('layouts.app')
@section('page_title', 'Criteria — MoviePickr')
@section('footer_pb', 'pb-20 sm:pb-6')
@section('scripts')
    @vite(['resources/js/custom/criteriaForm.js'])
@endsection
@section('content')
<div class="max-w-2xl mx-auto px-4 py-10 pb-24 sm:pb-4">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">Movie Criteria</h1>
        <p class="text-gray-500 text-sm mt-1">Fill in what you want, leave the rest blank.</p>
    </div>

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
                            value="{{ old('primary_release_date_gte') }}">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">To</label>
                        <input type="text"
                            class="input-dark bg-input {{ $errors->has('primary_release_date_lte') ? 'border-danger' : '' }}"
                            name="primary_release_date_lte"
                            placeholder="{{ date('Y') }}"
                            value="{{ old('primary_release_date_lte') }}">
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
                                    {{ is_array(old('with_genres')) && in_array($genre->id, old('with_genres')) ? 'selected' : '' }}>
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
                                    {{ is_array(old('without_genres')) && in_array($genre->id, old('without_genres')) ? 'selected' : '' }}>
                                    {{ $genre->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Streaming --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Streaming</h3>
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Language</label>
                        @include('includes.languages')
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Services</label>
                        <select id="with_watch_providers" name="with_watch_providers[]" multiple>
                            @foreach($providersArray as $value)
                                <option value="{{ $value['id'] }}" data-logo="{{ $value['logo'] }}">{{ $value['name'] }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-600 mt-1">Shows services available in your region</p>
                    </div>
                </div>
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
                                name="vote_average_gte" placeholder="0" value="{{ old('vote_average_gte') }}">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Max Score</label>
                            <input type="text"
                                class="input-dark bg-input {{ $errors->has('vote_average_lte') ? 'border-danger' : '' }}"
                                name="vote_average_lte" placeholder="10" value="{{ old('vote_average_lte') }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Min Vote Count</label>
                        <input type="text"
                            class="input-dark bg-input {{ $errors->has('vote_count_gte') ? 'border-danger' : '' }}"
                            name="vote_count_gte" placeholder="10" value="{{ old('vote_count_gte') }}">
                        <p class="text-xs text-gray-600 mt-1">Filters out obscure untested films</p>
                    </div>
                </div>
            </div>

            @include('errors.error')

            {{-- Desktop actions --}}
            <div class="hidden sm:flex items-center justify-between">
                <button type="button" id="btn-reset" class="btn-secondary text-sm">Reset</button>
                <div class="flex gap-2">
                    <button type="submit" class="btn-accent" formaction="/movie">Find Movie</button>
                    <button type="submit" class="btn-secondary" formaction="/multiple">Find Multiple</button>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- Mobile sticky bottom bar --}}
<div class="fixed bottom-0 left-0 right-0 sm:hidden bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 px-4 py-3 z-40">
    <div class="flex gap-2">
        <button type="button" id="btn-reset-mobile" class="btn-secondary text-sm px-4">Reset</button>
        <button type="submit" form="criteria" formaction="/movie" class="btn-accent flex-1 text-center">Find Movie</button>
        <button type="submit" form="criteria" formaction="/multiple" class="btn-secondary flex-1 text-center">Multiple</button>
    </div>
</div>

@endsection
