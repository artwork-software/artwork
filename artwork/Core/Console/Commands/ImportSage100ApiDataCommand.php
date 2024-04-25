<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\SageAssignedDataService;
use Artwork\Modules\Budget\Services\SageNotAssignedDataService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Sage100\Services\Sage100Service;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Console\Command;

class ImportSage100ApiDataCommand extends Command
{
    protected $signature = 'artwork:import-sage100-api-data {count?} {specificDay?}';

    protected $description = 'Get data from Sage100 and import it to budget.';

    public function __construct(
        private readonly Sage100Service $sage100Service
    ) {
        parent::__construct();
    }

    public function handle(
        ProjectService $projectService,
        ColumnService $columnService,
        SageApiSettingsService $sageApiSettingsService,
        SageAssignedDataService $sageAssignedDataService,
        SageNotAssignedDataService $sageNotAssignedDataService
    ): int {
        return $this->sage100Service->importDataToBudget(
            $this->argument('count'),
            $this->argument('specificDay'),
            $projectService,
            $columnService,
            $sageApiSettingsService,
            $sageAssignedDataService,
            $sageNotAssignedDataService
        );
    }
}
