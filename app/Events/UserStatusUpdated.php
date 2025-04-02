<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class UserStatusUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $userId;
    public $status;

    public function __construct($userId, $status)
    {
        $this->userId = $userId;
        $this->status = $status;
    }

    public function broadcastOn()
    {
        return new Channel('users.status');
    }
}
