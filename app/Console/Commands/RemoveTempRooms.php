<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Room;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RemoveTempRooms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     * $temporary
     */
    protected $signature = 'app:remove-temporary-rooms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Command delete all rooms where are temporary';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $rooms = Room::where('end_date', '<=', Carbon::now())->where('temporary', true)->get();

        foreach ($rooms as $room){
            $this->info('Room ' . $room->name . ' deleted');
            $room->delete();
        }

        return CommandAlias::SUCCESS;
    }
}
