<div class="owl-carousel owl-theme {{ $name }} mt-3">
    @php
        $height = 'auto';
        foreach ($genres as $genre) {
            if(strlen($genre) > 25){
                $height = '46px';
            }
        }
    @endphp
    @foreach ($allMovies->results as $result)
        @if(array_key_exists('release_date', $result))
            <div class='poster item'>
                <div class="movie">
                    <a href="{{ url('movie/'.$result->id) }}" style="flex-grow: 1" class="long-movie" data-name="{{ $result->title }}">
                        <h4 class="text-decoration-none" style="flex-grow: 1">{{ $result->title }}</h4>
                        <div style="margin-bottom: auto;">
                            <p style="margin-bottom: 0"><span style="font-weight: bold; color: var(--accent)">Year:</span> {{date('Y', strtotime($result->release_date))}}</p>
                            @if($genres !== [] || $genres !== null)
                                @if(in_array($result->id, array_keys($genres)))
                                    <p style="height: {{ $height }}">
                                        <span style="font-weight: bold; color: var(--accent)">Genres:</span> {{ $genres[$result->id] }}
                                    </p>
                                @endif
                            @endif
                        </div>
                        @if ($result->poster_path !== null)
                            <div class="poster-img" style="background-image: url({{ 'https://image.tmdb.org/t/p/w300'.$result->poster_path }})"></div>
                        @else
                            <div class="poster-img poster-placeholder text-center">
                                <h4 class="mx-4">Movie poster was not found :(</h4>
                            </div>
                        @endif
                    </a>
                </div>
            </div>
        @endif
    @endforeach
</div>


