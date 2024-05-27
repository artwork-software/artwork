<?php

namespace Artwork\Migrating\Jobs;

use Artwork\Migrating\Models\RoomImportModel;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class ImportRoom
{
    use Queueable;
    use InteractsWithQueue;

    public function __construct(private readonly RoomImportModel $roomImportModel)
    {
    }

    public function handle(RoomService $roomService): void
    {
        $room = new Room();
        $room->area()->associate(Area::first());
        $room->user()->associate(User::first());
        $room->name = $this->roomImportModel->name;
        $room->description = $this->roomImportModel->description;
        $roomService->save($room);
    }
}
