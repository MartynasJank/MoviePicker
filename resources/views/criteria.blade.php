@extends('layouts.app')
@section('page_title', 'Criteria — MoviePickr')
@section('scripts')
    @vite(['resources/js/custom/criteriaForm.js'])
@endsection
@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">Movie Criteria</h1>
        <p class="text-gray-500 text-sm mt-1">Filter to find your perfect film</p>
    </div>

    <form method="POST" autocomplete="off" action="/movie" id="criteria">
        @csrf
        <div class="card p-6">

            {{-- Step indicators --}}
            <div class="flex items-start justify-center gap-1 mb-8">
                @foreach(['Years','Genres','Streaming','People','Scores'] as $i => $label)
                    @php $n = $i + 1; @endphp
                    <div class="flex flex-col items-center">
                        <div class="step-dot {{ $n === 1 ? 'active' : '' }}" id="step-dot-{{ $n }}" data-step="{{ $n }}">{{ $n }}</div>
                        <div class="step-label {{ $n === 1 ? 'active' : '' }}" id="step-label-{{ $n }}">{{ $label }}</div>
                    </div>
                    @if($n < 5)
                        <div class="step-line" id="step-line-{{ $n }}"></div>
                    @endif
                @endforeach
            </div>

            {{-- Step 1: Years --}}
            <div class="step-panel" id="step-1">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Start Year</label>
                        <input type="text"
                            class="input-dark bg-input {{ $errors->has('primary_release_date_gte') ? 'border-danger' : '' }}"
                            id="primary_release_date_gte"
                            name="primary_release_date_gte"
                            placeholder="1874"
                            value="{{ old('primary_release_date_gte') }}">
                        <p class="text-xs text-gray-600 mt-1">Oldest on record: 1874</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">End Year</label>
                        <input type="text"
                            class="input-dark bg-input {{ $errors->has('primary_release_date_lte') ? 'border-danger' : '' }}"
                            id="primary_release_date_lte"
                            name="primary_release_date_lte"
                            placeholder="{{ date('Y') }}"
                            value="{{ old('primary_release_date_lte') }}">
                        <p class="text-xs text-gray-600 mt-1">Default: current year</p>
                    </div>
                </div>
            </div>

            {{-- Step 2: Genres --}}
            <div class="step-panel hidden" id="step-2">
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Include Genres</label>
                        <select name="with_genres[]" id="with_genres" multiple>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}"
                                    {{ is_array(old('with_genres')) && in_array($genre->id, old('with_genres')) ? 'selected' : '' }}>
                                    {{ $genre->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-600 mt-1">Default: all genres</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Exclude Genres</label>
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

            {{-- Step 3: Language / Streaming --}}
            <div class="step-panel hidden" id="step-3">
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Language</label>
                        @include('includes.languages')
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Streaming Services</label>
                        <select id="with_watch_providers" name="with_watch_providers[]" multiple>
                            @foreach($providersArray as $value)
                                <option value="{{ $value['id'] }}" data-logo="{{ $value['logo'] }}">{{ $value['name'] }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-600 mt-1">Shows services available in your region</p>
                    </div>
                </div>
            </div>

            {{-- Step 4: People --}}
            <div class="step-panel hidden" id="step-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Actor</label>
                        <input placeholder="Actor name" type="text" class="cast"
                            multiple="multiple" name="with_cast" id="with_cast">
                        <p class="text-xs text-gray-600 mt-1">Type to search actors</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Crew</label>
                        <input placeholder="Director, writer…" type="text" class="crew"
                            multiple="multiple" name="with_crew" id="with_crew">
                        <p class="text-xs text-gray-600 mt-1">Directors, writers, producers</p>
                    </div>
                </div>
            </div>

            {{-- Step 5: Scores --}}
            <div class="step-panel hidden" id="step-5">
                <div class="flex flex-col gap-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Min Score</label>
                            <input type="text"
                                class="input-dark bg-input {{ $errors->has('vote_average_gte') ? 'border-danger' : '' }}"
                                id="vote_average_gte" name="vote_average_gte"
                                placeholder="0" value="{{ old('vote_average_gte') }}">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Max Score</label>
                            <input type="text"
                                class="input-dark bg-input {{ $errors->has('vote_average_lte') ? 'border-danger' : '' }}"
                                id="vote_average_lte" name="vote_average_lte"
                                placeholder="10" value="{{ old('vote_average_lte') }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1.5">Min Vote Count</label>
                        <input type="text"
                            class="input-dark bg-input {{ $errors->has('vote_count_gte') ? 'border-danger' : '' }}"
                            id="vote_count_gte" name="vote_count_gte"
                            placeholder="10" value="{{ old('vote_count_gte') }}">
                        <p class="text-xs text-gray-600 mt-1">Filters out obscure untested films</p>
                    </div>
                </div>
            </div>

            @include('errors.error')

            {{-- Navigation --}}
            <div class="flex items-center justify-between mt-8 pt-5 border-t border-white/5">
                <button type="button" id="btn-reset" class="btn-secondary text-sm">Reset</button>
                <div class="flex gap-2">
                    <button type="button" id="btn-prev" class="btn-secondary hidden">← Back</button>
                    <button type="button" id="btn-next" class="btn-accent">Next →</button>
                    <button type="submit" id="btn-find-movie" class="btn-accent hidden" formaction="/movie">Find Movie</button>
                    <button type="submit" id="btn-find-multiple" class="btn-secondary hidden" formaction="/multiple">Find Multiple</button>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection
