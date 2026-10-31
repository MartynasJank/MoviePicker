<?php

use App\Events\CollabStateUpdated;
use App\Models\CollabBatch;
use App\Models\User;
use Tests\RefreshFeatureDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshFeatureDatabase::class);

function securityBatch(array $attributes = []): CollabBatch
{
    return CollabBatch::create(array_merge([
        'token' => 'testroom',
        'movies' => [['id' => 1, 'title' => 'First'], ['id' => 2, 'title' => 'Second']],
        'expires_at' => now()->addDay(),
    ], $attributes));
}

it('rejects unsafe or malformed collaborative movie data', function (array $movie, string $field) {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/batch/collab', [
        'movies' => [$movie, ['id' => 2, 'title' => 'Second']],
    ])->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('collab_batches', 0);
})->with([
    'HTML in poster path' => [['id' => 1, 'poster_path' => '/bad.jpg" onerror="alert(1)'], 'movies.0.poster_path'],
    'invalid identifier' => [['id' => '1"><script>'], 'movies.0.id'],
    'array title' => [['id' => 1, 'title' => []], 'movies.0.title'],
    'invalid date' => [['id' => 1, 'release_date' => '<img src=x>'], 'movies.0.release_date'],
]);

it('creates a room with validated movie data', function () {
    $this->actingAs(User::factory()->create())->postJson('/batch/collab', [
        'movies' => [
            ['id' => 1, 'title' => 'First', 'poster_path' => '/poster.jpg', 'ignored' => '<script>'],
            ['id' => 2, 'title' => 'Second'],
        ],
    ])->assertOk()->assertJsonStructure(['token']);

    expect(CollabBatch::first()->movies[0])->not->toHaveKey('ignored');
});

it('assigns a server identity and ignores forged participant IDs', function () {
    $batch = securityBatch();
    Event::fake([CollabStateUpdated::class]);

    $this->postJson('/batch/collab/testroom/join', ['userId' => 'victim', 'name' => 'Guest'])->assertOk();
    $identity = session('collab_participant_id');
    expect($identity)->toBeString()->not->toBe('victim');
    expect($batch->fresh()->participants[0]['userId'])->toBe($identity);

    $this->postJson('/batch/collab/testroom/ready', ['userId' => 'victim'])->assertOk();
    expect($batch->fresh()->ready)->toBe([$identity]);
    Event::assertDispatched(CollabStateUpdated::class, fn ($event) => $event->byId === $identity);
});

it('rejects room mutations without joined session membership', function (string $action) {
    securityBatch();
    Event::fake([CollabStateUpdated::class]);

    $this->postJson('/batch/collab/testroom/' . $action, ['userId' => 'forged'])->assertForbidden();

    Event::assertNotDispatched(CollabStateUpdated::class);
})->with(['vote/1', 'ready', 'refresh', 'restart', 'heartbeat', 'leave', 'remove-votes', 'voting']);

it('does not let participants remove someone else votes', function () {
    $batch = securityBatch([
        'participants' => [['userId' => 'mine', 'name' => 'Guest', 'last_seen' => now()->toISOString()]],
        'votes' => ['1' => ['victim']],
    ]);
    Event::fake([CollabStateUpdated::class]);

    $this->withSession(['collab_participant_id' => 'mine'])
        ->postJson('/batch/collab/testroom/remove-votes', ['targetUserId' => 'victim'])->assertForbidden();

    expect($batch->fresh()->votes)->toBe(['1' => ['victim']]);
    Event::assertNotDispatched(CollabStateUpdated::class);
});

it('removes only the departing participants votes and readiness', function () {
    $batch = securityBatch([
        'participants' => [['userId' => 'mine', 'name' => 'Guest', 'last_seen' => now()->toISOString()]],
        'votes' => ['1' => ['mine', 'other']],
        'restore_votes' => ['2' => ['mine']],
        'ready' => ['mine', 'other'],
        'try_again_votes' => ['mine'],
    ]);
    Event::fake([CollabStateUpdated::class]);

    $this->withSession(['collab_participant_id' => 'mine'])->postJson('/batch/collab/testroom/leave')->assertOk();

    expect($batch->fresh()->votes)->toBe(['1' => ['other']]);
    expect($batch->fresh()->restore_votes)->toBe(['2' => []]);
    expect($batch->fresh()->ready)->toBe(['other']);
    expect($batch->fresh()->try_again_votes)->toBe([]);
    Event::assertDispatched(CollabStateUpdated::class, fn ($event) => $event->eventType === 'vote_cleanup');
});

it('rejects expired rooms and overly long names', function () {
    $batch = securityBatch();
    $this->postJson('/batch/collab/testroom/join', ['name' => str_repeat('x', 81)])
        ->assertUnprocessable()->assertJsonValidationErrors('name');
    $batch->update(['expires_at' => now()->subMinute()]);
    $this->postJson('/batch/collab/testroom/join', ['name' => 'Guest'])->assertNotFound();
});

it('casts a vote under the joined session rather than a supplied victim ID', function () {
    $batch = securityBatch([
        'participants' => [
            ['userId' => 'mine', 'name' => 'Guest', 'last_seen' => now()->toISOString()],
            ['userId' => 'other', 'name' => 'Other', 'last_seen' => now()->toISOString()],
            ['userId' => 'third', 'name' => 'Third', 'last_seen' => now()->toISOString()],
        ],
    ]);
    Event::fake([CollabStateUpdated::class]);

    $this->withSession(['collab_participant_id' => 'mine'])
        ->postJson('/batch/collab/testroom/vote/1', ['userId' => 'other', 'type' => 'veto'])->assertOk();

    expect($batch->fresh()->votes)->toBe(['1' => ['mine']]);
    expect($batch->fresh()->movies)->toHaveCount(2);
    Event::assertDispatched(CollabStateUpdated::class, fn ($event) => $event->byId === 'mine');
});

it('creates rooms from watchlists up to the supported limit', function (int $count) {
    $this->actingAs(User::factory()->create())->postJson('/batch/collab', [
        'movies' => array_map(fn ($id) => ['id' => $id, 'title' => 'Title ' . $id], range(1, $count)),
        'media_type' => 'movie',
    ])->assertOk();

    expect(CollabBatch::first()->movies)->toHaveCount($count);
})->with([101, CollabBatch::MAX_MOVIES]);

it('rejects selections over the maximum supported size', function () {
    $this->actingAs(User::factory()->create())->postJson('/batch/collab', [
        'movies' => array_map(fn ($id) => ['id' => $id], range(1, CollabBatch::MAX_MOVIES + 1)),
    ])->assertUnprocessable()->assertJsonValidationErrors('movies');

    $this->assertDatabaseCount('collab_batches', 0);
});

