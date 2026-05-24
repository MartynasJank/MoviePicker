<?php

use App\Services\TmdbClient;
use App\Services\MovieService;

beforeEach(function () {
    $this->mock(TmdbClient::class);

    $this->mock(MovieService::class)
        ->shouldReceive('genres')->andReturn([])
        ->shouldReceive('tvGenres')->andReturn([])
        ->shouldReceive('buildProvidersArray')->andReturn([]);
});

// ── Movie criteria ────────────────────────────────────────────────────────────

it('renders movie criteria page successfully', function () {
    $this->get('/criteria')->assertOk();
});

it('passes userInput session to movie criteria view', function () {
    session(['userInput' => ['vote_count_gte' => 50, 'with_genres' => [28]]]);

    $this->get('/criteria')
        ->assertOk()
        ->assertViewHas('userInput', ['vote_count_gte' => 50, 'with_genres' => [28]]);
});

it('passes empty array when no movie session exists', function () {
    $this->get('/criteria')
        ->assertOk()
        ->assertViewHas('userInput', []);
});

// ── TV criteria ───────────────────────────────────────────────────────────────

it('renders tv criteria page successfully', function () {
    $this->get('/tv/criteria')->assertOk();
});

it('passes tvInput session to tv criteria view', function () {
    session(['tvInput' => ['vote_count_gte' => 20, 'with_cast' => [123]]]);

    $this->get('/tv/criteria')
        ->assertOk()
        ->assertViewHas('userInput', ['vote_count_gte' => 20, 'with_cast' => [123]]);
});

it('passes empty array when no tv session exists', function () {
    $this->get('/tv/criteria')
        ->assertOk()
        ->assertViewHas('userInput', []);
});

it('does not expose movie session on tv criteria page', function () {
    session([
        'userInput' => ['vote_count_gte' => 99],
        'tvInput'   => ['vote_count_gte' => 5],
    ]);

    $response = $this->get('/tv/criteria')->assertOk();

    expect($response->viewData('userInput'))->toBe(['vote_count_gte' => 5]);
});
