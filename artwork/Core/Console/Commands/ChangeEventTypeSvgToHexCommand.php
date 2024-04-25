<?php

namespace Artwork\Core\Console\Commands;

use Database\Seeders\ChangeEventTypeSvgToHexSeed;
use Illuminate\Console\Command;

class ChangeEventTypeSvgToHexCommand extends Command
{

    protected $signature = 'artwork:change-event-type-svg-to-hex';

    protected $description = 'Change event type svg to hex';

    public function handle(): void
    {
        $this->call(ChangeEventTypeSvgToHexSeed::class);
    }
}
