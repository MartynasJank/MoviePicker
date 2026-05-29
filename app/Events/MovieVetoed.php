<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MovieVetoed implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $token,
        public int    $movieId,
        public string $vetoedBy,
        public array  $remaining,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('batch.' . $this->token);
    }
}
