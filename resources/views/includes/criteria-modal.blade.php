@php
    if ($user_input === 'default') $user_input = [];
@endphp

<div id="modal-form" class="modal-wrap hidden">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-box">
        <button class="modal-close" data-modal-close aria-label="Close">✕</button>

        <form method="POST" autocomplete="off" action="/movie?a=true" id="modal-criteria">
            @csrf
            <div class="p-6">

                {{-- Step indicators --}}
                <div class="flex items-start justify-center gap-1 mb-8 overflow-x-auto pb-1">
                    @foreach(['Years','Genres','Streaming','People','Scores'] as $i => $label)
                        @php $n = $i + 1; @endphp
                        <div class="flex flex-col items-center">
                            <div class="step-dot {{ $n === 1 ? 'active' : '' }}" id="modal-step-dot-{{ $n }}" data-step="{{ $n }}">{{ $n }}</div>
                            <div class="step-label {{ $n === 1 ? 'active' : '' }}" id="modal-step-label-{{ $n }}">{{ $label }}</div>
                        </div>
                        @if($n < 5)
                            <div class="step-line" id="modal-step-line-{{ $n }}"></div>
                        @endif
                    @endforeach
                </div>

                {{-- Step 1: Years --}}
                <div class="modal-step-panel" id="modal-step-1">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Start Year</label>
                            <input type="text" class="input-dark {{ $errors->has('primary_release_date_gte') ? 'border-danger' : '' }}"
                                id="modal-primary_release_date_gte"
                                name="primary_release_date_gte"
                                placeholder="1874"
                                value="{{ $user_input['primary_release_date_gte'] ?? '' }}">
                            <p class="text-xs text-gray-600 mt-1">Oldest on record: 1874</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">End Year</label>
                            <input type="text" class="input-dark {{ $errors->has('primary_release_date_lte') ? 'border-danger' : '' }}"
                                id="modal-primary_release_date_lte"
                                name="primary_release_date_lte"
                                placeholder="{{ date('Y') }}"
                                value="{{ $user_input['primary_release_date_lte'] ?? '' }}">
                            <p class="text-xs text-gray-600 mt-1">Default: current year</p>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Genres --}}
                <div class="modal-step-panel hidden" id="modal-step-2">
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Include Genres</label>
                            <select name="with_genres[]" id="modal-with_genres" multiple>
                                @foreach ($all_genres as $genre)
                                    <option value="{{ $genre->id }}"
                                        {{ isset($user_input['with_genres']) && in_array($genre->id, (array)$user_input['with_genres']) ? 'selected' : '' }}>
                                        {{ $genre->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-600 mt-1">Default: all genres</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Exclude Genres</label>
                            <select name="without_genres[]" id="modal-without_genres" multiple>
                                @foreach ($all_genres as $genre)
                                    <option value="{{ $genre->id }}"
                                        {{ isset($user_input['without_genres']) && in_array($genre->id, (array)$user_input['without_genres']) ? 'selected' : '' }}>
                                        {{ $genre->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Language / Streaming --}}
                <div class="modal-step-panel hidden" id="modal-step-3">
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Language</label>
                            @php
                                // Render the language select with modal-prefixed id
                                $modalLang = true;
                            @endphp
                            @include('includes.languages', ['modalMode' => true, 'selectedLang' => $user_input['with_original_language'] ?? 'en'])
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Streaming Services</label>
                            <select id="modal-with_watch_providers" name="with_watch_providers[]" multiple>
                                @foreach($providersArray as $value)
                                    <option value="{{ $value['id'] }}"
                                        data-logo="{{ $value['logo'] }}"
                                        {{ isset($user_input['with_watch_providers']) && in_array($value['id'], (array)$user_input['with_watch_providers']) ? 'selected' : '' }}>
                                        {{ $value['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Step 4: People --}}
                <div class="modal-step-panel hidden" id="modal-step-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Actor</label>
                            <input placeholder="Actor name" type="text" class="modal-cast"
                                multiple="multiple" name="with_cast" id="modal-with_cast">
                            <p class="text-xs text-gray-600 mt-1">Type to search actors</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Crew</label>
                            <input placeholder="Director, writer…" type="text" class="modal-crew"
                                multiple="multiple" name="with_crew" id="modal-with_crew">
                            <p class="text-xs text-gray-600 mt-1">Directors, writers, producers</p>
                        </div>
                    </div>
                </div>

                {{-- Step 5: Scores --}}
                <div class="modal-step-panel hidden" id="modal-step-5">
                    <div class="flex flex-col gap-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1.5">Min Score</label>
                                <input type="text" class="input-dark {{ $errors->has('vote_average_gte') ? 'border-danger' : '' }}"
                                    id="modal-vote_average_gte" name="vote_average_gte"
                                    placeholder="0" value="{{ $user_input['vote_average_gte'] ?? '' }}">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1.5">Max Score</label>
                                <input type="text" class="input-dark {{ $errors->has('vote_average_lte') ? 'border-danger' : '' }}"
                                    id="modal-vote_average_lte" name="vote_average_lte"
                                    placeholder="10" value="{{ $user_input['vote_average_lte'] ?? '' }}">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1.5">Min Vote Count</label>
                            <input type="text" class="input-dark {{ $errors->has('vote_count_gte') ? 'border-danger' : '' }}"
                                id="modal-vote_count_gte" name="vote_count_gte"
                                placeholder="10" value="{{ $user_input['vote_count_gte'] ?? '' }}">
                            <p class="text-xs text-gray-600 mt-1">Default: 10</p>
                        </div>
                    </div>
                </div>

                @include('errors.error')

                {{-- Navigation --}}
                <div class="flex items-center justify-between mt-8 pt-5 border-t border-white/5">
                    <button type="button" id="modal-btn-prev" class="btn-secondary hidden">← Back</button>
                    <div class="flex gap-2 ml-auto">
                        <button type="button" id="modal-btn-next" class="btn-accent">Next →</button>
                        <button type="submit" id="modal-btn-find-movie" class="btn-accent hidden" formaction="/movie?a=true">Find Movie</button>
                        <button type="submit" id="modal-btn-find-multiple" class="btn-secondary hidden" formaction="/multiple?a=true">Find Multiple</button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>
