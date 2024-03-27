<?php

namespace App\Console\Commands;

use Database\Seeders\ChangeEventTypeSvgToHexSeed;
use Illuminate\Console\Command;

class ChangeEventTypeSVG extends Command
{

    protected $signature = 'app:change-event-type';

    protected $description = 'Change event type svg to hex';

    public function handle(): void
    {
        $this->call(ChangeEventTypeSvgToHexSeed::class);
    }
}
