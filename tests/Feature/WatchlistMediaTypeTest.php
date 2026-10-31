<?php

use App\Models\User;
use Tests\RefreshFeatureDatabase;

uses(RefreshFeatureDatabase::class);

it('saves a movie and TV show with the same TMDB ID independently', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/watchlist/add', ['tmdb_id' => 123, 'title' => 'Movie', 'type' => 'movie'])
        ->assertOk()->assertJson(['saved' => true]);
    $this->postJson('/watchlist/add', ['tmdb_id' => 123, 'title' => 'Show', 'type' => 'tv'])
        ->assertOk()->assertJson(['saved' => true]);

    $this->assertDatabaseHas('watchlist', ['user_id' => $user->id, 'tmdb_id' => 123, 'type' => 'movie']);
    $this->assertDatabaseHas('watchlist', ['user_id' => $user->id, 'tmdb_id' => 123, 'type' => 'tv']);
    $this->assertDatabaseCount('watchlist', 2);

    $this->postJson('/watchlist/toggle', ['tmdb_id' => 123, 'title' => 'Show', 'type' => 'tv'])
        ->assertOk()->assertJson(['saved' => false]);
    $this->assertDatabaseHas('watchlist', ['user_id' => $user->id, 'tmdb_id' => 123, 'type' => 'movie']);
    $this->assertDatabaseMissing('watchlist', ['user_id' => $user->id, 'tmdb_id' => 123, 'type' => 'tv']);
});

it('changes status and deletes only the requested media type owned by the user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    foreach ([$owner, $other] as $user) {
        foreach (['movie', 'tv'] as $type) {
            $user->watchlist()->create(['tmdb_id' => 123, 'title' => 'Title', 'type' => $type, 'status' => 'saved']);
        }
    }

    $this->actingAs($owner)->patchJson('/watchlist/123/status', ['type' => 'tv', 'status' => 'watched'])->assertOk();
    $this->assertDatabaseHas('watchlist', ['user_id' => $owner->id, 'type' => 'tv', 'status' => 'watched']);
    $this->assertDatabaseHas('watchlist', ['user_id' => $owner->id, 'type' => 'movie', 'status' => 'saved']);
    $this->assertDatabaseHas('watchlist', ['user_id' => $other->id, 'type' => 'tv', 'status' => 'saved']);

    $this->deleteJson('/watchlist/123?type=tv')->assertOk();
    $this->assertDatabaseMissing('watchlist', ['user_id' => $owner->id, 'type' => 'tv']);
    $this->assertDatabaseCount('watchlist', 3);
});

it('defaults legacy requests to movies without changing TV entries', function () {
    $user = User::factory()->create();
    $user->watchlist()->create(['tmdb_id' => 123, 'title' => 'Show', 'type' => 'tv', 'status' => 'saved']);

    $this->actingAs($user)->postJson('/watchlist/toggle', ['tmdb_id' => 123, 'title' => 'Movie'])->assertOk();
    $this->assertDatabaseCount('watchlist', 2);
    $this->deleteJson('/watchlist/123')->assertOk();
    $this->assertDatabaseCount('watchlist', 1);
    $this->assertDatabaseHas('watchlist', ['type' => 'tv']);
});

it('rejects an invalid media type on status and delete requests', function () {
    $this->actingAs(User::factory()->create())->patchJson('/watchlist/123/status', ['type' => 'mixed', 'status' => 'watched'])
        ->assertUnprocessable()->assertJsonValidationErrors('type');
    $this->deleteJson('/watchlist/123?type=mixed')->assertUnprocessable()->assertJsonValidationErrors('type');
});

it('requires authentication for watchlist mutations', function () {
    $this->postJson('/watchlist/add', ['tmdb_id' => 123, 'title' => 'Movie'])->assertUnauthorized();
    $this->patchJson('/watchlist/123/status', ['status' => 'watched'])->assertUnauthorized();
    $this->deleteJson('/watchlist/123')->assertUnauthorized();
});

it('marks carousel items as saved by both media type and TMDB ID', function () {
    $this->actingAs(User::factory()->create());
    $html = view('includes.carousel', [
        'allMovies' => ['results' => [
            ['id' => 123, 'title' => 'Movie', 'media_type' => 'movie', 'release_date' => '2020-01-01'],
            ['id' => 123, 'name' => 'Show', 'media_type' => 'tv', 'first_air_date' => '2020-01-01'],
        ]],
        'genres' => [], 'showSave' => true, 'savedIds' => ['tv:123'],
    ])->render();

    expect(substr_count($html, 'data-saved="1"'))->toBe(1);
    expect(substr_count($html, 'data-saved="0"'))->toBe(1);
    expect($html)->toContain('data-media-type="tv"', 'data-media-type="movie"');
});

it('refuses a rollback that would discard colliding movie and TV entries', function () {
    $user = User::factory()->create();
    foreach (['movie', 'tv'] as $type) {
        $user->watchlist()->create(['tmdb_id' => 123, 'title' => 'Title', 'type' => $type]);
    }
    $migration = require database_path('migrations/2026_10_31_102205_include_media_type_in_watchlist_unique_index.php');

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Cannot roll back');

    $this->assertDatabaseCount('watchlist', 2);
});
