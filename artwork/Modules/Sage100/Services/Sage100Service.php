<?php

namespace Artwork\Modules\Sage100\Services;

use App\Sage100\Sage100;
use Artwork\Modules\Project\Services\ProjectService;

class Sage100Service
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {
    }


    public function getData(int $count)
    {
        return app(Sage100::class)->getData([
            "startIndex" => 0,
            "count" => $count,
        ]);
    }

    public function importDataToBudget(): void
    {
        $data = $this->getData(1000);

        foreach ($data as $item) {
            //dd($item['KstTraeger']);
            $projects = $this->projectService->getProjectsByCostCenter($item['KstTraeger']);

            foreach ($projects as $project) {
                dd($project->table);
            }
        }
    }
}
