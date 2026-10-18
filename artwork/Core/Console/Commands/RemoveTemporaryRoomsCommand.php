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
        // end_date kommt aus einem Datumsfeld (00:00 Uhr) und ist der letzte gültige Tag:
        // erst entfernen, wenn dieser Tag vorbei ist, nicht schon am Morgen des Tages.
        $rooms = Room::where('end_date', '<', Carbon::today())->where('temporary', true)->get();

        foreach ($rooms as $room) {
            $this->info('Room ' . $room->name . ' deleted');
            $room->delete();
        }

        return CommandAlias::SUCCESS;
    }
}
