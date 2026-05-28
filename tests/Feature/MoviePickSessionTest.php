<?php

use App\Services\TmdbClient;
use App\Services\MovieService;

beforeEach(function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('discover')
        ->andReturn([
            'results'       => [['id' => 550, 'title' => 'Fight Club']],
            'total_pages'   => 1,
            'total_results' => 1,
        ]);

    $this->partialMock(MovieService::class, function ($mock) {
        $mock->shouldReceive('getUserCountry')->andReturn('US');
        $mock->shouldReceive('resolvePage')->andReturn(1);
        $mock->shouldReceive('pickRandom')->andReturn(['id' => 550]);
        $mock->shouldReceive('pickBatch')->andReturn([]);
        $mock->shouldReceive('genres')->andReturn([]);
        $mock->shouldReceive('genresMap')->andReturn([]);
        $mock->shouldReceive('buildProvidersArray')->andReturn([]);
    });
});

it('saves submitted criteria in userInput on first submit', function () {
    $this->post('/movie', ['vote_count_gte' => '20', 'with_genres' => ['28']]);

    expect(session('userInput'))->toMatchArray(['vote_count_gte' => '20']);
});

it('overwrites userInput when new criteria is submitted via POST', function () {
    session(['userInput' => ['vote_count_gte' => '99']]);

    $this->post('/movie', ['vote_count_gte' => '5']);

    expect(session('userInput.vote_count_gte'))->toBe('5');
});

it('keeps existing userInput on GET re-roll', function () {
    session(['userInput' => ['vote_count_gte' => '99']]);

    $this->get('/movie');

    expect(session('userInput.vote_count_gte'))->toBe('99');
});

it('clears userInput and redirects on ?i reset', function () {
    session(['userInput' => ['vote_count_gte' => '20']]);

    $this->get('/movie?i=1')->assertRedirect('/movie');

    expect(session('userInput'))->toBeNull();
});

it('does not redirect on ?i when no session exists', function () {
    $this->post('/movie', ['vote_count_gte' => '5'])
        ->assertRedirect();

    // Should reach the discover step, not loop back
    expect(session('userInput'))->not->toBeNull();
});

it('sets default criteria when ?i=new is used with no prior session', function () {
    $this->get('/movie?i=new')->assertRedirect();

    expect(session('userInput'))->toMatchArray([
        'with_original_language'   => 'en',
        'primary_release_date_gte' => 1990,
        'vote_average_gte'         => 7,
        'vote_count_gte'           => 100,
    ]);
});

it('replaces existing session with defaults when ?i=new is used', function () {
    session(['userInput' => ['vote_count_gte' => '50', 'with_original_language' => 'fr']]);

    $this->get('/movie?i=new')->assertRedirect();

    expect(session('userInput.vote_count_gte'))->toBe(100);
    expect(session('userInput.with_original_language'))->toBe('en');
});

it('strips empty string values from submitted criteria', function () {
    $this->post('/movie', ['with_original_language' => '', 'vote_count_gte' => '5']);

    expect(session('userInput'))->not->toHaveKey('with_original_language');
    expect(session('userInput.vote_count_gte'))->toBe('5');
});
