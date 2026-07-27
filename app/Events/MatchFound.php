<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchFound implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $matchId,
        public string $player1,
        public string $player2,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("match.{$this->matchId}");
    }

    public function broadcastAs(): string
    {
        return 'match.found';
    }
}