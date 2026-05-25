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
        $mock->shouldReceive('randomMovie')->andReturn(['id' => 550]);
        $mock->shouldReceive('pickBatch')->andReturn([]);
        $mock->shouldReceive('genres')->andReturn([]);
        $mock->shouldReceive('movieGenresMap')->andReturn([]);
        $mock->shouldReceive('buildProvidersArray')->andReturn([]);
    });
});

it('saves submitted criteria in userInput on first submit', function () {
    $this->post('/movie', ['vote_count_gte' => '20', 'with_genres' => ['28']]);

    expect(session('userInput'))->toMatchArray(['vote_count_gte' => '20']);
});

it('keeps the original userInput when criteria already exists', function () {
    session(['userInput' => ['vote_count_gte' => '99']]);

    $this->post('/movie', ['vote_count_gte' => '5']);

    // resolveSessionCriteria does not overwrite an existing session
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
