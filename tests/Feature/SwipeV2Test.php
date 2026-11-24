<?php

use App\Models\User;
use App\Services\MovieService;
use App\Services\TmdbClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Tests\RefreshFeatureDatabase;

uses(RefreshFeatureDatabase::class);

beforeEach(function () {
    config(['api.admin_email' => 'admin@example.test']);
});

it('requires authentication on every swipe v2 endpoint', function (string $path) {
    $this->getJson($path)->assertUnauthorized();
})->with(['/admin/swipe-v2', '/admin/swipe-v2/feed', '/admin/swipe-v2/movie/123']);

it('forbids regular users on every swipe v2 endpoint', function (string $path) {
    $this->actingAs(User::factory()->create(['email' => 'viewer@example.test']))
        ->getJson($path)->assertForbidden();
})->with(['/admin/swipe-v2', '/admin/swipe-v2/feed', '/admin/swipe-v2/movie/123']);

it('renders the admin experiment with only movie watchlist IDs belonging to the admin', function () {
    $admin = User::factory()->create(['email' => 'admin@example.test']);
    $admin->watchlist()->create(['tmdb_id' => 123, 'title' => 'Saved movie', 'type' => 'movie']);
    $admin->watchlist()->create(['tmdb_id' => 456, 'title' => 'Saved TV', 'type' => 'tv']);
    User::factory()->create()->watchlist()->create(['tmdb_id' => 789, 'title' => 'Other movie', 'type' => 'movie']);

    $this->actingAs($admin)->get('/admin/swipe-v2')->assertOk()
        ->assertViewHas('watchlistIds', [123])->assertSee('Admin lab');
});

it('discovers a random page with the swipe v1 defaults without changing movie or TV criteria', function (int $excludePage) {
    $admin = User::factory()->create(['email' => 'admin@example.test']);
    $service = $this->partialMock(MovieService::class);
    $service->shouldReceive('getUserCountry')->once()->andReturn('LT');
    $service->shouldReceive('randomPage')->once()->with(10, $excludePage)->andReturn(7);
    $criteria = [
        'with_original_language' => 'en',
        'primary_release_date_gte' => 1990,
        'vote_average_gte' => 6,
        'vote_count_gte' => 50,
    ];
    $tmdb = $this->mock(TmdbClient::class);
    $tmdb->shouldReceive('discover')->once()->with($criteria, 'LT')
        ->andReturn(['results' => [['id' => 1, 'title' => 'Page one']], 'total_pages' => 10, 'total_results' => 200]);
    $tmdb->shouldReceive('discover')->once()->with(array_merge($criteria, ['page' => 7]), 'LT')
        ->andReturn(['results' => [['id' => 123, 'title' => 'Random movie']], 'total_pages' => 10]);

    $this->actingAs($admin)->withSession(['userInput' => ['with_genres' => 28], 'tvInput' => ['with_genres' => 35]])
        ->getJson('/admin/swipe-v2/feed' . ($excludePage ? '?exclude_page=' . $excludePage : ''))->assertOk()
        ->assertJsonPath('movies.0.id', 123)->assertJsonPath('movies.0.title', 'Random movie')
        ->assertJsonPath('movies.0.url', route('movie', ['id' => 123]))
        ->assertJsonPath('page', 7)->assertJsonPath('has_more', true)
        ->assertSessionHas('userInput', ['with_genres' => 28])->assertSessionHas('tvInput', ['with_genres' => 35]);
})->with([0, 3]);

it('rejects invalid discovery pages', function (string $page) {
    $this->actingAs(User::factory()->create(['email' => 'admin@example.test']))
        ->getJson('/admin/swipe-v2/feed?exclude_page=' . $page)
        ->assertUnprocessable()->assertJsonValidationErrors('exclude_page');
})->with(['0', '501', 'invalid']);

it('returns an empty feed when discovery has no matches', function () {
    $this->partialMock(MovieService::class)->shouldReceive('getUserCountry')->andReturn('LT');
    $this->mock(TmdbClient::class)->shouldReceive('discover')->once()->andReturn(['results' => [], 'total_pages' => 0]);

    $this->actingAs(User::factory()->create(['email' => 'admin@example.test']))
        ->getJson('/admin/swipe-v2/feed')->assertOk()->assertExactJson(['movies' => [], 'page' => 1, 'has_more' => false]);
});

it('reuses the first discovery response when only one usable page exists', function (int $pages, int $total) {
    $service = $this->partialMock(MovieService::class);
    $service->shouldReceive('getUserCountry')->andReturn('LT');
    $this->mock(TmdbClient::class)->shouldReceive('discover')->once()
        ->andReturn(['results' => [['id' => 123, 'title' => 'Only page']], 'total_pages' => $pages, 'total_results' => $total]);

    $this->actingAs(User::factory()->create(['email' => 'admin@example.test']))
        ->getJson('/admin/swipe-v2/feed')->assertOk()
        ->assertJsonPath('movies.0.id', 123)->assertJsonPath('page', 1)->assertJsonPath('has_more', false);
})->with([[1, 20], [2, 21]]);

it('returns movie information and prefers official YouTube trailers without a YouTube key', function () {
    config(['api.YOUTUBE' => null]);
    $this->mock(TmdbClient::class)->shouldReceive('movie')->with(123)->once()->andReturn((object) [
        'overview' => 'A film worth watching.', 'release_date' => '2024-05-01', 'runtime' => 112,
        'vote_average' => 7.8, 'vote_count' => 100, 'poster_path' => '/poster.jpg',
        'genres' => [(object) ['name' => 'Drama']],
        'videos' => (object) ['results' => [
            (object) ['site' => 'Vimeo', 'type' => 'Trailer', 'key' => 'wrong-site1', 'official' => true],
            (object) ['site' => 'YouTube', 'type' => 'Teaser', 'key' => 'teaser12345', 'official' => true],
            (object) ['site' => 'YouTube', 'type' => 'Trailer', 'key' => 'fan12345678'],
            (object) ['site' => 'YouTube', 'type' => 'Trailer', 'key' => 'official123', 'official' => true],
        ]],
    ]);

    $this->actingAs(User::factory()->create(['email' => 'admin@example.test']))
        ->getJson('/admin/swipe-v2/movie/123')->assertOk()->assertExactJson([
            'trailer' => 'official123', 'overview' => 'A film worth watching.', 'year' => '2024',
            'runtime' => 112, 'genres' => 'Drama', 'vote_average' => 7.8, 'poster_path' => '/poster.jpg',
        ]);
});

it('keeps details usable when trailers and ratings are unavailable', function () {
    $this->mock(TmdbClient::class)->shouldReceive('movie')->with(123)->once()->andReturn((object) [
        'title' => 'Unrated film',
        'videos' => (object) ['results' => [(object) ['site' => 'YouTube', 'type' => 'Trailer', 'key' => 'bad?key']]],
    ]);

    $this->actingAs(User::factory()->create(['email' => 'admin@example.test']))
        ->getJson('/admin/swipe-v2/movie/123')->assertOk()
        ->assertJsonPath('trailer', null)->assertJsonPath('vote_average', null);
});

it('returns a retryable error when TMDB is unavailable', function (string $path, string $method) {
    $this->partialMock(MovieService::class)->shouldReceive('getUserCountry')->andReturn('LT');
    $this->mock(TmdbClient::class)->shouldReceive($method)->once()
        ->andThrow(new ConnectException('Offline', new Request('GET', 'https://api.themoviedb.org')));

    $this->actingAs(User::factory()->create(['email' => 'admin@example.test']))
        ->getJson($path)->assertStatus(502)->assertJsonStructure(['message']);
})->with([['/admin/swipe-v2/feed', 'discover'], ['/admin/swipe-v2/movie/123', 'movie']]);

it('uses an unofficial YouTube trailer when no official trailer exists', function () {
    expect(app(MovieService::class)->getSwipeTrailer([
        (object) ['site' => 'YouTube', 'type' => 'Trailer', 'key' => 'fan12345678'],
    ]))->toBe('fan12345678');
});
