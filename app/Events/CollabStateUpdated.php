<?php

namespace App\Events;

use App\Models\CollabBatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CollabStateUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public array $state;

    public function __construct(
        public string $token,
        CollabBatch   $batch,
        public string $eventType,
        public string $byName     = '',
        public string $byId       = '',
        public string $movieTitle = '',
        public array  $winner     = [],
    )
    {
        $slim = fn(array $movies) => array_values(array_map(fn($m) => [
            'id'             => $m['id'],
            'poster_path'    => $m['poster_path'] ?? null,
            'title'          => $m['title'] ?? $m['name'] ?? '',
            'name'           => $m['name'] ?? $m['title'] ?? '',
            'vote_average'   => $m['vote_average'] ?? 0,
            'media_type'     => $m['media_type'] ?? null,
            'release_date'   => $m['release_date'] ?? null,
            'first_air_date' => $m['first_air_date'] ?? null,
            'genres'         => $m['genres'] ?? '',
        ], $movies));

        $this->state = [
            'movies'        => $slim($batch->movies ?? []),
            'graveyard'     => $slim($batch->graveyard ?? []),
            'votes'         => $batch->votes ?? [],
            'restore_votes' => $batch->restore_votes ?? [],
            'ready'         => array_values($batch->ready ?? []),
            'refresh_votes' => array_values($batch->refresh_votes ?? []),
            'participants'  => array_values(
                collect($batch->participants ?? [])
                    ->filter(fn($p) => Carbon::parse($p['last_seen'])->gt(now()->subMinutes(3)))
                    ->values()
                    ->all()
            ),
        ];
    }

    public function broadcastOn(): Channel
    {
        return new Channel('batch.' . $this->token);
    }

    public function broadcastAs(): string
    {
        return 'CollabStateUpdated';
    }
}
