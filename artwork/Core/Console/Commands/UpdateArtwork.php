<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\ProjectManagementBuilder\Services\ProjectManagementBuilderService;
use Database\Seeders\ProjectManagementBuilderSeed;
use Illuminate\Console\Command;

class UpdateArtwork extends Command
{

    public function __construct(
        private readonly ProjectManagementBuilderService $projectManagementBuilderService
    ) {
        parent::__construct();
    }

    protected $signature = 'artwork:update';

    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Artwork Update Command is running');

        if ($this->projectManagementBuilderService->getProjectManagementBuilder()->isEmpty()) {
            $this->call(ProjectManagementBuilderSeed::class);
            $this->info('Project Management Builder Seed has been called');
        } else {
            $this->info('Project Management Builder Seed already exists');
        }

        $this->info('Artwork Update Command has finished');
    }
}
