<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserWentOffline implements ShouldBroadcastNow
{
    public $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new Channel('user-status');
    }

    public function broadcastWith()
    {
        return ['user_id' => $this->userId];
    }
}