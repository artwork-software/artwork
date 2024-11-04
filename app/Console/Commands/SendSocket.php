<?php

namespace App\Console\Commands;

use Artwork\Modules\Event\Events\EventDeleted;
use Artwork\Modules\Event\Events\EventUpdated;
use Artwork\Modules\Event\Models\Event;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Console\Command;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SendSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-socket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        broadcast(new EventDeleted(Event::find(26474)));
    }
}
