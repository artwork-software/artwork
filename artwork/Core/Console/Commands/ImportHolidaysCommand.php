<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Holidays\Import\HolidayImport;
use Illuminate\Console\Command;

class ImportHolidaysCommand extends Command
{

    protected $signature = 'artwork:import-holidays';

    protected $description = 'Imports holidays of current year';

    public function handle(): void
    {
        $this->call(HolidayImport::class);
    }
}