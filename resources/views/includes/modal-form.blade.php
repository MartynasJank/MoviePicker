<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="myModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content custom-modal">
            @if($user_input == 'default')
                @php
                    $user_input = [];
                @endphp
            @endif
            <form method="POST" autocomplete="off" action="/movie?a=true" class="custom-border" id="criteria">
                @csrf
                <div id="smartwizard">
                    <ul>
                        <li><a href="#step-1">Years</a></li>
                        <li><a href="#step-2">Genres</a></li>
                        <li><a href="#step-3">Language / Streaming</a></li>
                        <li><a href="#step-4">People</a></li>
                        <li><a href="#step-5">Scores</a></li>
                    </ul>
                    <div>
                        <div id="step-1" class="">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="primary_release_date_gte">Start Year</label>
                                    <input type="text" class="form-control bg-input movie-input border {{ ($errors->has('primary_release_date_gte')) ?  'border-danger' :  ''}}" id="primary_release_date_gte" name="primary_release_date_gte" placeholder="1874" value="{{ $user_input['primary_release_date_gte'] ?? '' }}">
                                    <small id="primary_release_date_gte" class="form-text text-muted">Oldest movie in databse was released in 1874</small>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="primary_release_date_lte">End Year</label>
                                    <input type="text" class="form-control bg-input movie-input border {{ ($errors->has('primary_release_date_lte')) ?  'border-danger' :  ''}}" id="primary_release_date_lte" name="primary_release_date_lte" placeholder="2020" value="{{ $user_input['primary_release_date_lte'] ?? '' }}">
                                    <small id="primary_release_date_lte" class="form-text text-muted">Default: Current year</small>
                                </div>
                            </div>
                        </div>
                        <div id="step-2" class="">
                            <div class="form-group">
                                <label for="with_genres">Genres</label>
                                <select
                                    name="with_genres[]"
                                    id="with_genres"
                                    class="selectpicker form-control bg-input"
                                    data-actions-box="true"
                                    data-style="btn btn-white"
                                    title="Nothing selected"
                                    multiple>
                                    @foreach ($all_genres as $genre)
                                        <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                                    @endforeach
                                </select>
                                <small id="genres" class="form-text text-muted">Default: All | Select multiple</small>
                            </div>
                                @if(array_key_exists('with_genres', $user_input))
                                    @php $s = implode(',', $user_input['with_genres']) @endphp
                                    <script>
                                        var s = "{{ $s }}";
                                        var s_array = JSON.parse("[" + s + "]");
                                        $('#with_genres').selectpicker('val', s_array);
                                    </script>
                                @endif
                            <div class="form-group">
                                <label for="without_genres">Without Genres</label>
                                <select
                                    name="without_genres[]"
                                    id="without_genres"
                                    class="selectpicker form-control bg-input"
                                    data-actions-box="true"
                                    data-style="btn btn-white"
                                    title="Nothing selected"
                                    multiple>
                                    @foreach ($all_genres as $genre)
                                        <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                                    @endforeach
                                </select>
                                <small id="genres" class="form-text text-muted">Default: none | Select multiple</small>
                            </div>
                            @if(array_key_exists('without_genres', $user_input))
                                @php $ws = implode(',', $user_input['without_genres']) @endphp
                                <script>
                                    var ws = "{{ $ws }}";
                                    var ws_array = JSON.parse("[" + ws + "]");
                                    $('#without_genres').selectpicker('val', ws_array);
                                </script>
                            @endif
                        </div>
                        <div id="step-3">
                            @include('includes.languages')
                            @if(array_key_exists('with_original_language', $user_input))
                            <script>
                                let element = document.getElementById('with_original_language');
                                element.value = "{{ $user_input['with_original_language'] }}";
                            </script>
                            @endif
                            <div class="form-group">
                                <label for="with_original_language">Original Movie Language</label>
                                <select
                                    id="with_watch_providers"
                                    name="with_watch_providers[]"
                                    class="selectpicker form-control bg-input"
                                    data-live-search="true"
                                    multiple data-actions-box="true"
                                    data-style="btn"
                                    multiple>
                                    <option value="">All</option>
                                    @foreach($providersArray as $value)
                                        <option value="{{ $value['id'] }}" data-content="<img src='{{ $value['logo'] }}'><span style='margin-left: 10px'>{{ $value['name'] }}</span>"></option>
                                    @endforeach
                                </select>
                            </div>
                            <small id="genres" class="form-text text-muted">Select a streaming service</small>
                            @if(array_key_exists('with_watch_providers', $user_input))
                                <script>
                                    let element = document.getElementById('with_watch_providers');
                                    element.value = 8;
                                </script>
                            @endif
                        </div>
                        <div id="step-4">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="with_cast">Actor</label>
                                    <input
                                        placeholder="Name"
                                        type="text"
                                        class="cast"
                                        multiple="multiple"
                                        name="with_cast"
                                        id="with_cast">
                                    <small id="with_cast" class="form-text text-muted">Names of actors</small>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="with_crew">Crew</label>
                                    <input
                                        placeholder="Name"
                                        type="text"
                                        class="crew"
                                        multiple="multiple"
                                        name="with_crew"
                                        id="with_crew">
                                    <small id="with_crew" class="form-text text-muted">Names of Producers, writers, directors, etc.</small>
                                </div>
                            </div>
                        </div>
                        <div id="step-5">
                            <div class="form-row">
                                <div class="form-group col-md-6 scores">
                                    <label for="vote_average_gte">Lowest score</label>
                                    <input type="text" class="form-control bg-input movie-input border {{ ($errors->has('vote_average_gte')) ?  'border-danger' :  ''}}" id="vote_average_gte" name="vote_average_gte" placeholder="0" value="{{ $user_input['vote_average_gte'] ?? '' }}">
                                    <small id="vote_average_gte" class="form-text text-muted">Default: 0</small>
                                </div>
                                <div class="form-group col-md-6 scores">
                                    <label for="vote_average_lte">Highest score</label>
                                    <input type="text" class="form-control bg-input movie-input border {{ ($errors->has('vote_average_lte')) ?  'border-danger' :  ''}}" id="vote_average_lte" name="vote_average_lte" placeholder="10" value="{{ $user_input['vote_average_lte'] ?? '' }}">
                                    <small id="vote_average_lte" class="form-text text-muted">Default: 10</small>
                                </div>
                            </div>
                            <div class="row">
                                <label for="vote_count_gte">Minimum vote count</label>
                                <input type="text" class="form-control bg-input movie-input border {{ ($errors->has('vote_count_gte')) ?  'border-danger' :  ''}}" id="vote_count_gte" name="vote_count_gte" placeholder="10" value="{{ $user_input['vote_count_gte'] ?? '' }}">
                                <small id="vote_count_gte" class="form-text text-muted">Default: 10 (vote count is from TMDB)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            @include('errors.error')
        </div>
    </div>
</div>
