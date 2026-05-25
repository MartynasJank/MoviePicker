<?php

use App\Services\TmdbClient;

// ── Helpers ───────────────────────────────────────────────────────────────────

function credit(array $overrides = []): object
{
    return (object) array_merge([
        'id'            => rand(1000, 9999),
        'media_type'    => 'movie',
        'vote_count'    => 100,
        'episode_count' => 20,
        'genre_ids'     => [18],
        'popularity'    => 50.0,
        'release_date'  => '2020-01-01',
        'first_air_date'=> '2020-01-01',
    ], $overrides);
}

function personWith(array $cast = [], array $crew = []): object
{
    return (object)[
        'id'   => 1,
        'name' => 'Test Person',
        'combined_credits' => (object)[
            'cast' => $cast,
            'crew' => $crew,
        ],
    ];
}

// ── Page renders ──────────────────────────────────────────────────────────────

it('renders the person page successfully', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(personWith(cast: [credit()]));

    $this->get('/person/1')->assertOk();
});

it('returns 404 when personDetail throws', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andThrow(new \RuntimeException('not found'));

    $this->get('/person/1')->assertNotFound();
});

it('returns 404 when person has no id', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn((object)['id' => null]);

    $this->get('/person/1')->assertNotFound();
});

// ── hasMovieCast / hasMovieCrew ───────────────────────────────────────────────

it('sets hasMovieCast true when cast contains a movie credit', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(personWith(cast: [credit(['media_type' => 'movie'])]));

    $this->get('/person/1')
        ->assertOk()
        ->assertViewHas('hasMovieCast', true);
});

it('sets hasMovieCast false when cast contains only tv credits', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(personWith(cast: [credit(['media_type' => 'tv'])]));

    $this->get('/person/1')
        ->assertOk()
        ->assertViewHas('hasMovieCast', false);
});

it('sets hasMovieCrew true when crew contains a movie credit', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(personWith(crew: [credit(['media_type' => 'movie'])]));

    $this->get('/person/1')
        ->assertOk()
        ->assertViewHas('hasMovieCrew', true);
});

it('sets hasMovieCrew false when crew contains only tv credits', function () {
    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(personWith(crew: [credit(['media_type' => 'tv'])]));

    $this->get('/person/1')
        ->assertOk()
        ->assertViewHas('hasMovieCrew', false);
});

// ── hasTvCast / hasTvCrew ─────────────────────────────────────────────────────

it('sets hasTvCast true when cast has a qualifying tv show', function () {
    $show = credit(['media_type' => 'tv', 'vote_count' => 50, 'episode_count' => 10]);

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(personWith(cast: [$show]));

    $this->get('/person/1')
        ->assertOk()
        ->assertViewHas('hasTvCast', true);
});

it('sets hasTvCast false when cast tv show has too few episodes', function () {
    $show = credit(['media_type' => 'tv', 'vote_count' => 50, 'episode_count' => 2]);

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(personWith(cast: [$show]));

    $this->get('/person/1')
        ->assertOk()
        ->assertViewHas('hasTvCast', false);
});

it('sets hasTvCast false when cast tv show has too few votes', function () {
    $show = credit(['media_type' => 'tv', 'vote_count' => 3, 'episode_count' => 20]);

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(personWith(cast: [$show]));

    $this->get('/person/1')
        ->assertOk()
        ->assertViewHas('hasTvCast', false);
});

it('sets hasTvCast false when cast tv show is a talk show', function () {
    $show = credit(['media_type' => 'tv', 'vote_count' => 50, 'episode_count' => 20, 'genre_ids' => [10767]]);

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(personWith(cast: [$show]));

    $this->get('/person/1')
        ->assertOk()
        ->assertViewHas('hasTvCast', false);
});

it('sets hasTvCrew true when crew has a qualifying tv show', function () {
    $show = credit(['media_type' => 'tv', 'vote_count' => 50, 'episode_count' => 10]);

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(personWith(crew: [$show]));

    $this->get('/person/1')
        ->assertOk()
        ->assertViewHas('hasTvCrew', true);
});

it('sets hasTvCrew false when crew has no qualifying tv shows', function () {
    $show = credit(['media_type' => 'tv', 'vote_count' => 3, 'episode_count' => 1]);

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn(personWith(crew: [$show]));

    $this->get('/person/1')
        ->assertOk()
        ->assertViewHas('hasTvCrew', false);
});

// ── All false when no combined_credits ────────────────────────────────────────

it('returns all role flags false when person has no combined_credits', function () {
    $person = (object)['id' => 1, 'name' => 'No Credits'];

    $this->mock(TmdbClient::class)
        ->shouldReceive('personDetail')
        ->andReturn($person);

    $this->get('/person/1')
        ->assertOk()
        ->assertViewHas('hasMovieCast', false)
        ->assertViewHas('hasMovieCrew', false)
        ->assertViewHas('hasTvCast', false)
        ->assertViewHas('hasTvCrew', false);
});
