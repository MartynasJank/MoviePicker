@extends('layouts.app')
@section('page_title', 'TV Show Criteria — MoviePickr')
@section('footer_pb', 'pb-24')
@section('scripts')
    @vite(['resources/js/custom/criteriaForm.js'])
@endsection
@section('content')
<div class="max-w-2xl mx-auto px-4 py-10 pb-24">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">TV Show Criteria</h1>
        <p class="text-gray-500 text-sm mt-1">Fill in what you want, leave the rest blank.</p>
    </div>

    <form method="POST" autocomplete="off" action="/tv/pick" id="criteria">
        @csrf
        <div class="flex flex-col gap-4">

            {{-- First Air Date --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">First Air Date</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">From</label>
                        <input type="text"
                            class="input-dark bg-input {{ $errors->has('first_air_date_gte') ? 'border-danger' : '' }}"
                            name="first_air_date_gte"
                            placeholder="1990"
                            value="{{ old('first_air_date_gte') }}">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">To</label>
                        <input type="text"
                            class="input-dark bg-input {{ $errors->has('first_air_date_lte') ? 'border-danger' : '' }}"
                            name="first_air_date_lte"
                            placeholder="{{ date('Y') }}"
                            value="{{ old('first_air_date_lte') }}">
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

            {{-- Language --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Language</h3>
                @include('includes.languages')
            </div>

            {{-- Streaming --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Streaming</h3>
                <select id="with_watch_providers" name="with_watch_providers[]" multiple>
                    @foreach($providersArray as $value)
                        <option value="{{ $value['id'] }}" data-logo="{{ $value['logo'] }}">{{ $value['name'] }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-600 mt-1">Shows services available in your region</p>
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
                        <p class="text-xs text-gray-600 mt-1">Filters out obscure shows</p>
                    </div>
                </div>
            </div>

            @include('errors.error')

        </div>
    </form>
</div>

{{-- Sticky bottom bar --}}
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 px-4 py-3 z-40">
    <div class="max-w-2xl mx-auto flex gap-2 sm:justify-end">
        <button type="button" id="btn-reset-mobile" class="btn-secondary text-sm px-4 sm:hidden">Reset</button>
        <button type="submit" form="criteria" formaction="/tv/multiple" class="btn-secondary flex-1 sm:flex-none text-center">Multiple</button>
        <button type="submit" form="criteria" formaction="/tv/pick" class="btn-accent long-single flex-1 sm:flex-none text-center">Find Show</button>
    </div>
</div>

@endsection
