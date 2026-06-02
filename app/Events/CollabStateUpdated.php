<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollabStateUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $token,
        public string $eventType,
        public string $byName     = '',
        public string $byId       = '',
        public string $movieTitle = '',
        public array  $delta      = [],
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('batch.' . $this->token);
    }

    public function broadcastAs(): string
    {
        return 'CollabStateUpdated';
    }
}
