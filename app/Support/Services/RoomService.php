<?php

namespace App\Support\Services;

use App\Enums\NotificationConstEnum;
use App\Models\User;
use Room;

class RoomService
{
    protected ?NotificationService $notificationService = null;
    protected ?\stdClass $notificationData = null;
    protected NewHistoryService $history;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
        $this->history = new NewHistoryService('Room');
    }




}
