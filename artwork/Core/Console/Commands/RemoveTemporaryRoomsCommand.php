<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RemoveTemporaryRoomsCommand extends Command
{
    protected $signature = 'artwork:remove-temporary-rooms';

    protected $description = 'This Command delete all rooms where are temporary';

    public function handle(): int
    {
        $rooms = Room::where('end_date', '<=', Carbon::now())->where('temporary', true)->get();

        foreach ($rooms as $room) {
            $this->info('Room ' . $room->name . ' deleted');
            $room->delete();
        }

        return CommandAlias::SUCCESS;
    }
}
