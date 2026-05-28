<?php

use App\Services\TmdbClient;
use App\Services\MovieService;

beforeEach(function () {
    // Mock TMDB so no real HTTP calls are made
    $this->mock(TmdbClient::class)
        ->shouldReceive('discoverTv')
        ->andReturn([
            'results'       => [['id' => 1001, 'name' => 'Test Show', 'first_air_date' => '2020-01-01', 'genre_ids' => []]],
            'total_pages'   => 1,
            'total_results' => 1,
        ])
        ->shouldReceive('tvGenres')
        ->andReturn('{"genres":[]}');

    // partialMock lets resolveSessionCriteria and normaliseShows run for real
    // so session-manipulation tests can assert on actual session state
    $this->partialMock(MovieService::class, function ($mock) {
        $mock->shouldReceive('getUserCountry')->andReturn('US')
            ->shouldReceive('resolvePage')->andReturn(1)
            ->shouldReceive('pickRandom')->andReturn(['id' => 1001])
            ->shouldReceive('genres')->andReturn([])
            ->shouldReceive('pickBatch')->andReturn([])
            ->shouldReceive('genresMap')->andReturn([])
            ->shouldReceive('buildProvidersArray')->andReturn([]);
    });
});

it('always overwrites tvInput when criteria is submitted', function () {
    session(['tvInput' => ['vote_count_gte' => 999, 'with_cast' => [42]]]);

    $this->post('/tv/pick', ['vote_count_gte' => '5']);

    expect(session('tvInput'))->toBe(['vote_count_gte' => '5']);
});

it('clears tvPersonRollIds when new criteria is submitted', function () {
    session([
        'tvPersonRollIds' => [101, 202, 303],
        'tvInput'         => ['with_cast' => [42]],
    ]);

    $this->post('/tv/pick', ['vote_count_gte' => '5']);

    expect(session('tvPersonRollIds'))->toBeNull();
});

it('clears tvPersonRollIds on reset with ?i param', function () {
    session([
        'tvInput'         => ['vote_count_gte' => '5'],
        'tvPersonRollIds' => [101, 202],
    ]);

    // ?i triggers a session reset + redirect
    $this->get('/tv/pick?i=1')->assertRedirect('/tv/pick');

    expect(session('tvInput'))->toBeNull();
    expect(session('tvPersonRollIds'))->toBeNull();
});

it('does not redirect on ?i reset when no session exists', function () {
    // handleSessionReset only redirects when session('tvInput') !== null
    $this->post('/tv/pick', ['vote_count_gte' => '5'])
        ->assertRedirect();

    expect(session('tvPersonRollIds'))->toBeNull();
});

it('clears tvPersonRollIds on modal submit with ?a', function () {
    session([
        'tvPersonRollIds' => [101, 202],
        'tvInput'         => ['vote_count_gte' => '5'],
    ]);

    $this->post('/tv/pick?a=1', ['vote_count_gte' => '8']);

    expect(session('tvPersonRollIds'))->toBeNull();
});

it('sets default tv criteria when ?i=new is used with no prior session', function () {
    $this->get('/tv/pick?i=new')->assertRedirect();

    expect(session('tvInput'))->toMatchArray([
        'with_original_language' => 'en',
        'first_air_date_gte'     => 1990,
        'vote_average_gte'       => 7,
        'vote_count_gte'         => 100,
    ]);
});

it('replaces existing session with defaults and clears tvPersonRollIds when ?i=new is used', function () {
    session([
        'tvInput'         => ['vote_count_gte' => '50', 'with_original_language' => 'fr'],
        'tvPersonRollIds' => [101, 202],
    ]);

    $this->get('/tv/pick?i=new')->assertRedirect();

    expect(session('tvInput.vote_count_gte'))->toBe(100);
    expect(session('tvInput.with_original_language'))->toBe('en');
    expect(session('tvPersonRollIds'))->toBeNull();
});

it('strips empty string values from submitted tv criteria', function () {
    $this->post('/tv/pick', ['with_original_language' => '', 'vote_count_gte' => '5']);

    expect(session('tvInput'))->not->toHaveKey('with_original_language');
    expect(session('tvInput.vote_count_gte'))->toBe('5');
});