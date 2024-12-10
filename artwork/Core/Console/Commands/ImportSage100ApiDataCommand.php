<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\SageAssignedDataCommentService;
use Artwork\Modules\Budget\Services\SageAssignedDataService;
use Artwork\Modules\Budget\Services\SageNotAssignedDataService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Sage100\Services\Sage100Service;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use Throwable;

class ImportSage100ApiDataCommand extends Command
{
    protected $signature = 'artwork:import-sage100-api-data {--delete-sage-data} {count?} {specificDay?}';

    protected $description = 'Get data from Sage100 and import it to budget.';

    public function __construct(
        private readonly Sage100Service $sage100Service,
        private readonly LoggerInterface $logger,
        private readonly ProjectService $projectService,
        private readonly ColumnService $columnService,
        private readonly SageApiSettingsService $sageApiSettingsService,
        private readonly SageAssignedDataService $sageAssignedDataService,
        private readonly SageNotAssignedDataService $sageNotAssignedDataService,
        private readonly SageAssignedDataCommentService $sageAssignedDataCommentService,
    ) {
        parent::__construct();
    }
    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        if ($this->option('delete-sage-data')) {
            return $this->deleteSageData();
        }

        if ($this->sageApiSettingsService->getFirst() === null) {
            $this->error('Sage API settings not found. Please configure settings in artworks Tool-Settings.');
            return 1;
        }

        return $this->sage100Service->importDataToBudget(
            $this->argument('count'),
            $this->argument('specificDay'),
            $this->projectService,
            $this->columnService,
            $this->sageApiSettingsService,
            $this->sageAssignedDataService,
            $this->sageNotAssignedDataService
        );
    }

    /**
     * @throws Throwable
     */
    private function deleteSageData(): int
    {
        try {
            return $this->sage100Service->deleteSageData(
                $this->sageAssignedDataCommentService,
                $this->sageAssignedDataService,
                $this->sageNotAssignedDataService,
            );
        } catch (Throwable $t) {
            $this->logger->debug('Could not delete sage data for reason: ' . $t->getMessage());
            throw $t;
        }
    }
}
