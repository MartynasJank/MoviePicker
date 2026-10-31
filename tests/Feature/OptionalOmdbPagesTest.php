<?php

use App\Services\MovieService;
use App\Services\TmdbClient;
use Illuminate\Support\Facades\Http;

it('renders movie and TV details from TMDB when the OMDb key is rejected', function (string $path, string $method, string $similarMethod, string $genresMethod) {
    config(['api.OMDB' => 'expired-key', 'api.omdb_enabled' => true]);
    Http::preventStrayRequests();
    Http::fake(['private.omdbapi.com/*' => Http::response(['Response' => 'False'], 401)]);

    $details = (object) [
        'id' => 123,
        'imdb_id' => 'tt1234567',
        'external_ids' => (object) ['imdb_id' => 'tt1234567'],
        'title' => 'TMDB test title',
        'name' => 'TMDB test title',
        'overview' => 'TMDB description stays available.',
        'release_date' => '2020-01-01',
        'first_air_date' => '2020-01-01',
        'poster_path' => null,
        'backdrop_path' => null,
        'budget' => 0,
        'revenue' => 0,
        'runtime' => 90,
        'vote_average' => 7.8,
        'vote_count' => 1234,
        'recommendations' => (object) ['results' => []],
        'keywords' => (object) ['keywords' => [], 'results' => []],
        'videos' => (object) ['results' => []],
    ];
    $tmdb = $this->mock(TmdbClient::class);
    $tmdb->shouldReceive($method)->once()->andReturn($details);
    $tmdb->shouldReceive($similarMethod)->once()->andReturn(['results' => []]);
    $tmdb->shouldReceive($genresMethod)->once()->andReturn('{"genres":[]}');
    $service = $this->partialMock(MovieService::class);
    $service->shouldReceive('getUserCountry')->andReturn('LT');
    $service->shouldReceive('buildProvidersArray')->andReturn([]);

    $this->get($path)
        ->assertOk()
        ->assertViewHas('omdbInfo', null)
        ->assertSee('TMDB test title')
        ->assertSee('TMDB description stays available.')
        ->assertSee('7.8/10')
        ->assertSee('1,234 votes on TMDB')
        ->assertSee('https://www.imdb.com/title/tt1234567', false)
        ->assertDontSee('Awards')
        ->assertDontSee('IMDb Votes');

    Http::assertSentCount(1);
})->with([
    'movie' => ['/movie/123', 'movie', 'similarMovies', 'genres'],
    'TV' => ['/tv/123', 'tvShow', 'similarShows', 'tvGenres'],
]);

it('keeps OMDb ratings alongside TMDB without duplicating the IMDb link', function () {
    $html = view('includes.ratings', [
        'mediaType' => 'movie',
        'tmdbInfo' => (object) ['id' => 123, 'imdb_id' => 'tt1234567', 'vote_average' => 7.8, 'vote_count' => 1234],
        'omdbInfo' => (object) ['Ratings' => [
            (object) ['Source' => 'Internet Movie Database', 'Value' => '8.0/10'],
            (object) ['Source' => 'Rotten Tomatoes', 'Value' => '90%'],
            (object) ['Source' => 'Metacritic', 'Value' => 'N/A'],
        ]],
        'urls' => ['Internet Movie Database' => 'https://www.imdb.com/title/tt1234567'],
    ])->render();

    expect($html)->toContain('7.8/10', '8.0/10', '90%')
        ->not->toContain('View on IMDb', 'Metacritic', 'N/A');
});

it('shows an unrated state instead of a fabricated zero score', function () {
    $html = view('includes.ratings', [
        'mediaType' => 'tv',
        'tmdbInfo' => (object) ['id' => 123, 'vote_average' => 0, 'vote_count' => 0],
        'omdbInfo' => null,
        'urls' => [],
    ])->render();

    expect($html)->toContain('Not yet rated', '0 votes on TMDB')
        ->not->toContain('0.0/10', 'View on IMDb');
});
