<?php

use App\Services\TmdbClient;
use App\Services\MovieService;

// ── Helpers ───────────────────────────────────────────────────────────────────

function fakePerson(array $cast = [], array $crew = []): object
{
    return (object)[
        'id'   => 1,
        'name' => 'Bryan Cranston',
        'combined_credits' => (object)[
            'cast' => array_map(fn($c) => (object)$c, $cast),
            'crew' => array_map(fn($c) => (object)$c, $crew),
        ],
    ];
}

function tvCredit(array $overrides = []): array
{
    return array_merge([
        'id'            => rand(1000, 9999),
        'media_type'    => 'tv',
        'vote_count'    => 100,
        'episode_count' => 20,
        'genre_ids'     => [18], // Drama — passes all filters
    ], $overrides);
}

// ── TV roll ───────────────────────────────────────────────────────────────────

it('saves qualifying show ids in tvPersonRollIds session', function () {
    $show = tvCredit(['id' => 1399]);

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(fakePerson(cast: [$show]));

    $this->get('/person/1/roll/tv')->assertRedirect();

    expect(session('tvPersonRollIds'))->toContain(1399);
});

it('excludes shows with fewer than 5 episodes', function () {
    $main  = tvCredit(['id' => 100, 'episode_count' => 20]);
    $guest = tvCredit(['id' => 200, 'episode_count' => 2]);

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(fakePerson(cast: [$main, $guest]));

    $this->get('/person/1/roll/tv');

    expect(session('tvPersonRollIds'))
        ->toContain(100)
        ->not->toContain(200);
});

it('excludes shows with fewer than 10 votes', function () {
    $popular  = tvCredit(['id' => 300, 'vote_count' => 50]);
    $obscure  = tvCredit(['id' => 400, 'vote_count' => 3]);

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(fakePerson(cast: [$popular, $obscure]));

    $this->get('/person/1/roll/tv');

    expect(session('tvPersonRollIds'))
        ->toContain(300)
        ->not->toContain(400);
});

it('excludes talk shows, news, and reality TV genres', function () {
    $drama   = tvCredit(['id' => 500, 'genre_ids' => [18]]);
    $talk    = tvCredit(['id' => 601, 'genre_ids' => [10767]]); // Talk
    $news    = tvCredit(['id' => 602, 'genre_ids' => [10763]]); // News
    $reality = tvCredit(['id' => 603, 'genre_ids' => [10764]]); // Reality

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(fakePerson(cast: [$drama, $talk, $news, $reality]));

    $this->get('/person/1/roll/tv');

    expect(session('tvPersonRollIds'))
        ->toContain(500)
        ->not->toContain(601)
        ->not->toContain(602)
        ->not->toContain(603);
});

it('stores person name and id in tvInput for cast roll', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(fakePerson(cast: [tvCredit(['id' => 700])]));

    $this->get('/person/1/roll/tv?type=cast');

    expect(session('tvInput'))->toMatchArray([
        'with_cast'       => [1],
        'with_cast_names' => ['Bryan Cranston'],
        'vote_count_gte'  => 10,
    ]);
});

it('stores person name and id in tvInput for crew roll', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(fakePerson(crew: [tvCredit(['id' => 800])]));

    $this->get('/person/1/roll/tv?type=crew');

    expect(session('tvInput'))->toMatchArray([
        'with_crew'       => [1],
        'with_crew_names' => ['Bryan Cranston'],
        'vote_count_gte'  => 10,
    ]);
});

it('redirects back to person page when no qualifying shows exist', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(fakePerson(cast: [
            tvCredit(['episode_count' => 1]), // too few episodes
        ]));

    $this->get('/person/1/roll/tv')
        ->assertRedirect(route('person', 1));
});

// ── tvNext ────────────────────────────────────────────────────────────────────

it('redirects to a show from the tvPersonRollIds pool', function () {
    session(['tvPersonRollIds' => [201, 202, 203]]);

    $response = $this->get('/person/roll/tv/next');

    $response->assertRedirectContains('/tv/');
});

it('redirects to tv criteria when pool is empty', function () {
    $this->get('/person/roll/tv/next')
        ->assertRedirect('/tv/pick');
});

// ── Movie roll ────────────────────────────────────────────────────────────────

it('sets with_cast in userInput session for cast movie roll', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('discover')
        ->andReturn([
            'results'       => [['id' => 550, 'title' => 'Fight Club']],
            'total_pages'   => 1,
            'total_results' => 1,
        ]);

    $this->mock(MovieService::class)
        ->shouldReceive('getUserCountry')->andReturn('US')
        ->shouldReceive('resolvePage')->andReturn(1)
        ->shouldReceive('randomMovie')->andReturn(['id' => 550]);

    $this->get('/person/1/roll/movie?type=cast');

    expect(session('userInput'))->toMatchArray(['with_cast' => [1]]);
});

it('sets with_crew in userInput session for crew movie roll', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('discover')
        ->andReturn([
            'results'       => [['id' => 550, 'title' => 'Fight Club']],
            'total_pages'   => 1,
            'total_results' => 1,
        ]);

    $this->mock(MovieService::class)
        ->shouldReceive('getUserCountry')->andReturn('US')
        ->shouldReceive('resolvePage')->andReturn(1)
        ->shouldReceive('randomMovie')->andReturn(['id' => 550]);

    $this->get('/person/1/roll/movie?type=crew');

    expect(session('userInput'))->toMatchArray(['with_crew' => [1]]);
});

// ── TV roll edge cases ────────────────────────────────────────────────────────

it('filters out movie credits from the tv cast pool', function () {
    $tvShow = tvCredit(['id' => 900]);
    $movie  = array_merge(tvCredit(['id' => 901]), ['media_type' => 'movie']);

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(fakePerson(cast: [$tvShow, $movie]));

    $this->get('/person/1/roll/tv');

    expect(session('tvPersonRollIds'))
        ->toContain(900)
        ->not->toContain(901);
});

it('deduplicates shows that appear multiple times in credits', function () {
    $show = tvCredit(['id' => 1000]);

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(fakePerson(cast: [$show, $show, $show]));

    $this->get('/person/1/roll/tv');

    expect(session('tvPersonRollIds'))->toHaveCount(1);
});

it('handles a person with no combined_credits gracefully', function () {
    $person = (object)['id' => 1, 'name' => 'No Credits'];

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn($person);

    $this->get('/person/1/roll/tv')
        ->assertRedirect(route('person', 1));
});
