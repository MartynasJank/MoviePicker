<div class="owl-carousel owl-theme {{ $name }} mt-3">
    @foreach ($allMovies->results as $result)
        @if(array_key_exists('release_date', $result))
            <div class='poster item'>
                    <div class="movie">
                        <a href="{{ url('movie/'.$result->id) }}">
                        <h4 class="text-decoration-none">{{ $result->title }}</h4>
                        <p class="flex-text">{{date('Y', strtotime($result->release_date))}}</p>
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

