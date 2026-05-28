<?php

use App\Services\TmdbClient;
use App\Services\MovieService;

beforeEach(function () {
    $this->mock(TmdbClient::class);

    $this->mock(MovieService::class)
        ->shouldReceive('genres')->andReturn([])
        ->shouldReceive('buildProvidersArray')->andReturn([]);
});

// ── Movie criteria ────────────────────────────────────────────────────────────

it('renders movie criteria page successfully', function () {
    $this->get('/criteria')->assertOk();
});

it('always passes empty userInput to movie criteria view (blank form)', function () {
    session(['userInput' => ['vote_count_gte' => 50, 'with_genres' => [28]]]);

    $this->get('/criteria')
        ->assertOk()
        ->assertViewHas('userInput', []);
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

it('always passes empty userInput to tv criteria view (blank form)', function () {
    session(['tvInput' => ['vote_count_gte' => 20, 'with_cast' => [123]]]);

    $this->get('/tv/criteria')
        ->assertOk()
        ->assertViewHas('userInput', []);
});

it('passes empty array when no tv session exists', function () {
    $this->get('/tv/criteria')
        ->assertOk()
        ->assertViewHas('userInput', []);
});

it('always passes empty userInput on tv criteria page regardless of session', function () {
    session([
        'userInput' => ['vote_count_gte' => 99],
        'tvInput'   => ['vote_count_gte' => 5],
    ]);

    $this->get('/tv/criteria')
        ->assertOk()
        ->assertViewHas('userInput', []);
});
