<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Holidays\Import\HolidayImport;
use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;

class ImportHolidaysCommand extends Command
{

    protected $signature = 'artwork:import-holidays';

    protected $description = 'Imports holidays of current year';

    public function handle(Dispatcher $dispatcher): void
    {
        $dispatcher->dispatch(new HolidayImport());
    }
}
