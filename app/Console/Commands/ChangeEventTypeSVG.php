<?php

namespace App\Console\Commands;

use Database\Seeders\ChangeEventTypeSvgToHexSeed;
use Illuminate\Console\Command;

class ChangeEventTypeSVG extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-event-type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change event type svg to hex';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call(ChangeEventTypeSvgToHexSeed::class);
    }
}
