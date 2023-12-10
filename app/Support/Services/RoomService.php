<?php

namespace App\Support\Services;

use Artwork\Modules\Room\Models\Room;

class RoomService
{
    protected ?\stdClass $notificationData = null;
    protected NewHistoryService $history;

    public function __construct(protected readonly NotificationService $notificationService)
    {
        $this->history = new NewHistoryService(Room::class);
    }




}
