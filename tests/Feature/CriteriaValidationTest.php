<?php

use App\Services\TmdbClient;
use App\Services\MovieService;

// Valid submissions need the controller to run, so mock its dependencies.
beforeEach(function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('discover')->andReturn(['results' => [['id' => 1]], 'total_pages' => 1, 'total_results' => 1])
        ->shouldReceive('discoverTv')->andReturn(['results' => [['id' => 1, 'name' => 'Show', 'first_air_date' => '2020-01-01', 'genre_ids' => []]], 'total_pages' => 1, 'total_results' => 1]);

    $this->mock(MovieService::class)
        ->shouldReceive('getUserCountry')->andReturn('US')
        ->shouldReceive('resolvePage')->andReturn(1)
        ->shouldReceive('pickRandom')->andReturn(['id' => 1])
        ->shouldReceive('resolveSessionCriteria')->andReturnUsing(fn($s) => $s)
        ->shouldReceive('pickBatch')->andReturn([])
        ->shouldReceive('genres')->andReturn([])
        ->shouldReceive('genresMap')->andReturn([])
        ->shouldReceive('buildProvidersArray')->andReturn([]);
});

// ── Movie criteria (POST /movie) ──────────────────────────────────────────────

it('accepts a valid movie criteria submission', function () {
    $this->post('/movie', ['vote_count_gte' => '50', 'vote_average_gte' => '5', 'vote_average_lte' => '9'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

it('accepts empty movie criteria (all fields nullable)', function () {
    $this->post('/movie', [])->assertSessionHasNoErrors();
});

it('rejects a movie start year below 1874', function () {
    $this->post('/movie', ['primary_release_date_gte' => '1800'])
        ->assertSessionHasErrors('primary_release_date_gte');
});

it('rejects a movie end year below 1874', function () {
    $this->post('/movie', ['primary_release_date_lte' => '1800'])
        ->assertSessionHasErrors('primary_release_date_lte');
});

it('rejects a movie year above the current year', function () {
    $future = (int) date('Y') + 1;

    $this->post('/movie', ['primary_release_date_gte' => $future])
        ->assertSessionHasErrors('primary_release_date_gte');
});

it('rejects a movie end year before the start year', function () {
    $this->post('/movie', ['primary_release_date_gte' => '2010', 'primary_release_date_lte' => '2005'])
        ->assertSessionHasErrors('primary_release_date_lte');
});

it('rejects a movie vote average above 10', function () {
    $this->post('/movie', ['vote_average_gte' => '11'])
        ->assertSessionHasErrors('vote_average_gte');
});

it('rejects a movie vote average below 0', function () {
    $this->post('/movie', ['vote_average_gte' => '-1'])
        ->assertSessionHasErrors('vote_average_gte');
});

it('rejects a movie highest score below lowest score', function () {
    $this->post('/movie', ['vote_average_gte' => '7', 'vote_average_lte' => '5'])
        ->assertSessionHasErrors('vote_average_lte');
});

it('rejects a negative movie vote count', function () {
    $this->post('/movie', ['vote_count_gte' => '-1'])
        ->assertSessionHasErrors('vote_count_gte');
});

// ── TV criteria (POST /tv/pick) ───────────────────────────────────────────────

it('accepts a valid tv criteria submission', function () {
    $this->post('/tv/pick', ['vote_count_gte' => '20', 'vote_average_gte' => '6', 'vote_average_lte' => '9'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

it('accepts empty tv criteria (all fields nullable)', function () {
    $this->post('/tv/pick', [])->assertSessionHasNoErrors();
});

it('rejects a tv start year below 1900', function () {
    $this->post('/tv/pick', ['first_air_date_gte' => '1800'])
        ->assertSessionHasErrors('first_air_date_gte');
});

it('rejects a tv end year below 1900', function () {
    $this->post('/tv/pick', ['first_air_date_lte' => '1800'])
        ->assertSessionHasErrors('first_air_date_lte');
});

it('rejects a tv year above the current year', function () {
    $future = (int) date('Y') + 1;

    $this->post('/tv/pick', ['first_air_date_gte' => $future])
        ->assertSessionHasErrors('first_air_date_gte');
});

it('rejects a tv end year before the start year', function () {
    $this->post('/tv/pick', ['first_air_date_gte' => '2015', 'first_air_date_lte' => '2010'])
        ->assertSessionHasErrors('first_air_date_lte');
});

it('rejects a tv vote average above 10', function () {
    $this->post('/tv/pick', ['vote_average_lte' => '11'])
        ->assertSessionHasErrors('vote_average_lte');
});

it('rejects a tv vote average below 0', function () {
    $this->post('/tv/pick', ['vote_average_gte' => '-1'])
        ->assertSessionHasErrors('vote_average_gte');
});

it('rejects a tv highest score below lowest score', function () {
    $this->post('/tv/pick', ['vote_average_gte' => '8', 'vote_average_lte' => '4'])
        ->assertSessionHasErrors('vote_average_lte');
});

it('rejects a negative tv vote count', function () {
    $this->post('/tv/pick', ['vote_count_gte' => '-5'])
        ->assertSessionHasErrors('vote_count_gte');
});
