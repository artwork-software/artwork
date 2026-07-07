<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Sage100\Services\Sage100Service;
use Illuminate\Console\Command;
use Throwable;

class ImportSage100ApiDataCommand extends Command
{
    protected $signature = 'artwork:import-sage100-api-data {--delete-sage-data} {count?} {specificDayFrom?} {specificDayTo?}';

    protected $description = 'Get data from Sage100 and import it to budget.';

    /**
     * @throws Throwable
     */
    public function handle(Sage100Service $sage100Service): int
    {
        if ($this->option('delete-sage-data')) {
            return $sage100Service->deleteSageData();
        }

        $specificDayFrom = $this->argument('specificDayFrom');
        $specificDayTo = $this->argument('specificDayTo') ?? $specificDayFrom;

        return $sage100Service->importDataToBudget(
            $this->argument('count'),
            $specificDayFrom,
            $specificDayTo,
        );
    }
}
