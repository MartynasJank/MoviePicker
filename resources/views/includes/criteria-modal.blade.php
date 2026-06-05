@php
    $modalMediaType = $modalMediaType ?? 'movie';
    $isTvModal      = $modalMediaType === 'tv';
    $yearGteField   = $isTvModal ? 'first_air_date_gte'   : 'primary_release_date_gte';
    $yearLteField   = $isTvModal ? 'first_air_date_lte'   : 'primary_release_date_lte';
    $yearLabel      = $isTvModal ? 'First Air Date'        : 'Release Year';

    if ($user_input === 'default') $user_input = [];
    $openYear     = !empty($user_input[$yearGteField] ?? '') || !empty($user_input[$yearLteField] ?? '');
    $openGenres   = !empty($user_input['with_genres'] ?? []) || !empty($user_input['without_genres'] ?? []);
    $openLanguage  = !empty($user_input['with_original_language'] ?? '') || !empty($user_input['with_origin_country'] ?? '');
    $openStreaming  = !empty($user_input['with_watch_providers'] ?? []);
    $openPeople    = !empty($user_input['with_cast'] ?? []) || !empty($user_input['with_crew'] ?? []);
    $openScores   = !empty($user_input['vote_average_gte'] ?? '') || !empty($user_input['vote_average_lte'] ?? '') || !empty($user_input['vote_count_gte'] ?? '');
@endphp

<div id="modal-form" class="modal-wrap hidden">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-box">
        <form method="POST" autocomplete="off" action="/movie?a=true" id="modal-criteria">
            @csrf
            <div class="modal-sticky-header">
                <div>
                    <h2 class="text-lg font-bold text-white">Adjust Filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Tap a section to expand. Leave anything blank to ignore it.</p>
                </div>
                <button type="button" class="modal-close" data-modal-close aria-label="Close">✕</button>
            </div>

            <div class="px-3 sm:px-5 flex flex-col">

                {{-- Years --}}
                <div class="accordion-section border-t border-white/5 {{ $openYear ? 'accordion-open' : '' }}">
                    <button type="button" class="accordion-header w-full flex items-center justify-between py-2.5 sm:py-3.5 text-left">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $yearLabel }}</h3>
                        <svg class="accordion-chevron w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="accordion-body">
                        <div class="pb-4 grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">From</label>
                                <input type="text" class="input-dark" name="{{ $yearGteField }}" placeholder="1874" value="{{ $user_input[$yearGteField] ?? '' }}">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">To</label>
                                <input type="text" class="input-dark" name="{{ $yearLteField }}" placeholder="{{ date('Y') }}" value="{{ $user_input[$yearLteField] ?? '' }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Genres --}}
                <div class="accordion-section border-t border-white/5 {{ $openGenres ? 'accordion-open' : '' }}">
                    <button type="button" class="accordion-header w-full flex items-center justify-between py-2.5 sm:py-3.5 text-left">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Genres</h3>
                        <svg class="accordion-chevron w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="accordion-body">
                        <div class="pb-4 flex flex-col gap-3">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Include</label>
                                <select name="with_genres[]" id="modal-with_genres" multiple>
                                    @foreach ($all_genres as $genre)
                                        <option value="{{ $genre->id }}"
                                            {{ isset($user_input['with_genres']) && in_array($genre->id, (array)$user_input['with_genres']) ? 'selected' : '' }}>
                                            {{ $genre->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Exclude</label>
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
                </div>

                {{-- Language & Country --}}
                <div class="accordion-section border-t border-white/5 {{ $openLanguage ? 'accordion-open' : '' }}">
                    <button type="button" class="accordion-header w-full flex items-center justify-between py-2.5 sm:py-3.5 text-left">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Language & Country</h3>
                        <svg class="accordion-chevron w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="accordion-body">
                        <div class="pb-4 flex flex-col gap-3">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Language</label>
                                @include('includes.languages', ['modalMode' => true, 'selectedLang' => $user_input['with_original_language'] ?? ''])
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Origin Country</label>
                                @include('includes.origin-country', ['modalMode' => true, 'selectedCountry' => $user_input['with_origin_country'] ?? ''])
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Streaming --}}
                <div class="accordion-section border-t border-white/5 {{ $openStreaming ? 'accordion-open' : '' }}">
                    <button type="button" class="accordion-header w-full flex items-center justify-between py-2.5 sm:py-3.5 text-left">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Streaming</h3>
                        <svg class="accordion-chevron w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="accordion-body">
                        <div class="pb-4">
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

                {{-- People --}}
                <div class="accordion-section border-t border-white/5 {{ $openPeople ? 'accordion-open' : '' }}">
                    <button type="button" class="accordion-header w-full flex items-center justify-between py-2.5 sm:py-3.5 text-left">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">People</h3>
                        <svg class="accordion-chevron w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="accordion-body">
                        <div class="pb-4 flex flex-col gap-3">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Actors</label>
                                <select id="modal-with_cast" name="with_cast[]" multiple></select>
                                <p class="text-xs text-gray-600 mt-1">Type to search, you can add multiple</p>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Crew</label>
                                <select id="modal-with_crew" name="with_crew[]" multiple></select>
                                <p class="text-xs text-gray-600 mt-1">Directors, writers, producers</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Scores --}}
                <div class="accordion-section border-t border-white/5 {{ $openScores ? 'accordion-open' : '' }}">
                    <button type="button" class="accordion-header w-full flex items-center justify-between py-2.5 sm:py-3.5 text-left">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Scores</h3>
                        <svg class="accordion-chevron w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="accordion-body">
                        <div class="pb-4 flex flex-col gap-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm text-gray-400 mb-1">Min Score</label>
                                    <input type="text" class="input-dark" name="vote_average_gte" placeholder="0" value="{{ $user_input['vote_average_gte'] ?? '' }}">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-400 mb-1">Max Score</label>
                                    <input type="text" class="input-dark" name="vote_average_lte" placeholder="10" value="{{ $user_input['vote_average_lte'] ?? '' }}">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Min Vote Count</label>
                                <input type="text" class="input-dark" name="vote_count_gte" placeholder="10" value="{{ $user_input['vote_count_gte'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                </div>

                @include('errors.error')

            </div>

            {{-- Actions --}}
            <div class="modal-sticky-footer">
                <button type="button" id="modal-btn-reset" class="btn-secondary text-sm">Reset</button>
                <div class="flex gap-2">
                    <button type="submit" class="btn-secondary text-sm long-single" formaction="/multiple?a=true">Multiple</button>
                    <button type="submit" class="btn-accent text-sm long-single" formaction="/movie?a=true">Find Movie</button>
                </div>
            </div>

        </form>
    </div>
</div>