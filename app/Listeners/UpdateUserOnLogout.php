<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Artwork\Modules\Shift\Support\SafeBroadcast;

class UpdateUserOnLogout
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // Update the user's status to offline
        // remove the user status from Redis
        app(\Artwork\Modules\User\Services\UserStatusService::class)->markOffline($event->user->id);

        // Broadcast the status update event
        SafeBroadcast::send(new \App\Events\UserStatusUpdated($event->user->id, 'offline'));
    }
}
