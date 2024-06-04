<?php

namespace Artwork\Migrating\Jobs;

use Artwork\Migrating\Models\RoomImportModel;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class ImportRoom
{
    use Queueable;
    use InteractsWithQueue;

    public function __construct(private readonly RoomImportModel $roomImportModel)
    {
    }

    public function handle(RoomService $roomService, UserService $userService): void
    {
        $room = new Room();
        $room->area()->associate(Area::first());
        $room->user()->associate($userService->getAdminUser());
        $room->name = $this->roomImportModel->name;
        $room->description = $this->roomImportModel->description;
        $roomService->save($room);
    }
}
